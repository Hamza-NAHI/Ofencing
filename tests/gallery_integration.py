#!/usr/bin/env python3
"""Gallery HTTP + disk checks. Disposable localhost DB only; Python + Pillow (test fixtures).
Uses the same OFENCING_TEST_* credentials/write consent as integration.py.
OFENCING_TEST_APACHE=yes also checks static draft privacy and denied upload extensions.
Optional OFENCING_TEST_REPO points to the local web checkout for on-disk deletion checks.
"""
import http.cookiejar
import io
import json
import os
from pathlib import Path
import re
import secrets
import struct
import urllib.error
import urllib.parse
import urllib.request
import zlib
from PIL import Image

BASE = os.environ.get('OFENCING_TEST_BASE_URL', '').rstrip('/') + '/'
if os.environ.get('OFENCING_TEST_ALLOW_WRITES') != 'yes' or urllib.parse.urlparse(BASE).hostname not in ['localhost', '127.0.0.1', '::1']:
    raise SystemExit('Only explicitly authorized disposable localhost installs are supported.')
PREFIX = 'gallery-test-' + secrets.token_hex(6)
REPO = Path(os.environ['OFENCING_TEST_REPO']) if os.environ.get('OFENCING_TEST_REPO') else None
APACHE = os.environ.get('OFENCING_TEST_APACHE') == 'yes'
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args): return None

class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def request(self, path, expected=200, method='GET', fields=None, files=None, headers=None, raw=None):
        headers = dict(headers or {})
        if files is not None:
            boundary='test-'+secrets.token_hex(12)
            parts=[]
            for key,value in (fields or {}).items():
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
            for filename,body,mime in files:
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="photos[]"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+body+b'\r\n')
            raw=b''.join(parts)+f'--{boundary}--\r\n'.encode()
            headers['Content-Type']='multipart/form-data; boundary='+boundary
        elif fields is not None:
            raw=urllib.parse.urlencode(fields).encode(); headers['Content-Type']='application/x-www-form-urlencoded'
        req=urllib.request.Request(BASE+path,data=raw,method=method,headers=headers)
        try:r=self.opener.open(req,timeout=60)
        except urllib.error.HTTPError as e:r=e
        body=r.read()
        assert r.status==expected,(path,r.status,expected,body[:400])
        return body,r.headers
    def token(self,path):
        return re.search(rb'name="csrf_token" value="([a-f0-9]+)"',self.request(path)[0])[1].decode()
    def post(self,path,fields,expected=303):return self.request(path,expected,'POST',fields=fields)

public=Client(); admin=Client()
for path in ['admin/gallery/','admin/gallery/create.php','admin/gallery/edit.php?id=1','admin/gallery/album.php?id=1',
             'admin/gallery/upload.php?id=1','admin/gallery/reorder.php','admin/gallery/set-cover.php','admin/gallery/save-photo.php',
             'admin/gallery/publish.php','admin/gallery/delete-photo.php?album=1&id=1','admin/gallery/delete-album.php?id=1','admin/gallery/image.php?id=1']:
    public.request(path,303)
public.request('api/gallery.php',405,'POST')
public.request('api/gallery.php?album[]=1',422)
csrf=admin.token('admin/login.php')
admin.post('admin/login.php',{'csrf_token':csrf,'username':os.environ['OFENCING_TEST_USERNAME'],'password':os.environ['OFENCING_TEST_PASSWORD']})
csrf=admin.token('admin/gallery/')
for path in ['admin/gallery/create.php','admin/gallery/upload.php?id=1','admin/gallery/reorder.php','admin/gallery/set-cover.php','admin/gallery/save-photo.php','admin/gallery/publish.php']:
    admin.post(path,{},403)
print('PASS gallery authentication, mutation CSRF and API methods/IDs')

created=[]
def create(title,published=0,order=0):
    fields={'title':title,'description':'Description <script>bad()</script> مرحباً','event_date':'','location':'Rabat',
            'is_published':published,'display_order':order,'csrf_token':csrf}
    _,headers=admin.post('admin/gallery/create.php',fields)
    album_id=int(urllib.parse.parse_qs(urllib.parse.urlparse(headers['Location']).query)['id'][0])
    created.append(album_id);return album_id

def album(album_id,expected=200):
    return json.loads(public.request(f'api/gallery.php?album={album_id}',expected)[0])

def listing():return json.loads(public.request('api/gallery.php')[0])['data']
def publish(album_id,value):admin.post('admin/gallery/publish.php',{'album_id':album_id,'is_published':value,'csrf_token':csrf})
def upload(album_id,files,expected=201,token=csrf,client=admin):
    body,headers=client.request(f'admin/gallery/upload.php?id={album_id}',expected,'POST',files=files,fields={'csrf_token':token},headers={'Accept':'application/json','X-CSRF-Token':token})
    assert headers.get_content_type()=='application/json'
    return json.loads(body)
def png(size=(80,60),color=(255,0,0,100)):
    im=Image.new('RGBA',size,color);out=io.BytesIO();im.save(out,'PNG');return out.getvalue()
def local_image_path(url):
    relative=url.split('/uploads/gallery/',1)[1]
    return REPO/'uploads/gallery'/relative

def remove_photo(album_id,photo_id):
    path=f'admin/gallery/delete-photo.php?album={album_id}&id={photo_id}'
    admin.request(path);admin.post(path,{'csrf_token':'wrong'},403);admin.post(path,{'csrf_token':csrf})

def remove_album(album_id):
    path=f'admin/gallery/delete-album.php?id={album_id}'
    admin.request(path);admin.post(path,{'csrf_token':'wrong'},403);admin.post(path,{'csrf_token':csrf})
    created.remove(album_id)

try:
    a=create(PREFIX+' <img src=x onerror=alert(1)>')
    b=create(PREFIX+' published empty',1,9999)
    assert not any(x['id']==a for x in listing())
    assert any(x['id']==b and x['photo_count']==0 and x['cover'] is None for x in listing())
    album(a,404);assert album(b)['data']['photos']==[]
    # A 6000x4000 camera JPEG exercises actual resampling and encoding.
    large=Image.effect_noise((6000,4000),75).convert('RGB');source=io.BytesIO();large.save(source,'JPEG',quality=72)
    camera=source.getvalue();large.close()
    first=upload(a,[('../../camera.jpg',camera,'application/octet-stream'),('fake.jpg',b'<?php echo "bad"; ?>','image/jpeg')],200)
    assert first['data']['uploaded']==1 and first['data']['failed']==1
    record=first['data']['results'][0]
    assert record['width']==1920 and record['height']==1280
    assert record['stored_bytes']<len(camera)
    second=upload(a,[('small.png',png(),'application/x-httpd-php')])['data']['results'][0]
    assert (second['width'],second['height'])==(80,60),'Do not upscale'
    # Orientation 6: stored output must be 40x80 and omit EXIF.
    portrait=Image.new('RGB',(80,40),'red'); exif=portrait.getexif();exif[274]=6;buf=io.BytesIO();portrait.save(buf,'JPEG',exif=exif)
    rotated=upload(a,[('orientation.jpg',buf.getvalue(),'image/jpeg')])['data']['results'][0]
    assert (rotated['width'],rotated['height'])==(40,80)
    # Public metadata is never available for this draft, even to an authenticated browser.
    album(a,404);admin.request(f'api/gallery.php?album={a}',404)
    preview,_=admin.request(f'admin/gallery/image.php?id={record["id"]}')
    assert max(Image.open(io.BytesIO(preview)).size)==640
    # A real draft URL learned from local storage must also be denied by Apache.
    if REPO:
        stored=[p for p in (REPO/f'uploads/gallery/album-{a}').iterdir() if not p.name.startswith('.')]
        assert len(stored)==6 and all(p.suffix in ['.webp','.jpg'] for p in stored)
        assert not any('camera' in p.name for p in stored)
        if APACHE:public.request(f'uploads/gallery/album-{a}/{stored[0].name}',403)
    publish(a,1)
    detail=album(a)['data'];photos=detail['photos']
    assert len(photos)==3 and detail['album']['cover']['id']==record['id']
    assert 'original_filename' not in json.dumps(detail) and '/workspace/' not in json.dumps(detail)
    paths=[]
    for photo in photos:
        for key,edge in [('image_url',1920),('thumbnail_url',640)]:
            raw,_=public.request(photo[key].removeprefix(urllib.parse.urlparse(BASE).path))
            im=Image.open(io.BytesIO(raw));assert max(im.size)<=edge and im.format=='WEBP'
            assert not im.getexif()
            if REPO:paths.append(local_image_path(photo[key]));assert paths[-1].is_file()
    print(f'PASS mixed multi-upload, {len(camera)} → {record["stored_bytes"]} bytes, 6000×4000 → 1920×1280, 640px thumbs, no upscaling, EXIF and static draft privacy')
    admin.post('admin/gallery/set-cover.php',{'csrf_token':csrf,'album_id':a,'photo_id':second['id']})
    assert album(a)['data']['album']['cover']['id']==second['id']
    admin.post('admin/gallery/reorder.php',{'csrf_token':csrf,'album_id':a,'photo_id':rotated['id'],'direction':'up'})
    assert [p['id'] for p in album(a)['data']['photos']]==[record['id'],rotated['id'],second['id']]
    admin.post('admin/gallery/save-photo.php',{'csrf_token':csrf,'album_id':a,'photo_id':record['id'],'caption':'Caption <svg onload=alert(1)>','alt_text':'مرحبا sur la piste'})
    detail=album(a)['data'];assert detail['photos'][0]['alt_text']=='مرحبا sur la piste'
    html=admin.request(f'admin/gallery/album.php?id={a}')[0].decode()
    assert '&lt;svg onload=alert(1)&gt;' in html and '<svg onload=alert(1)>' not in html
    for action,extra in [('set-cover.php',{}),('reorder.php',{'direction':'up'}),('save-photo.php',{'caption':'wrong','alt_text':''})]:
        admin.post('admin/gallery/'+action,{'csrf_token':csrf,'album_id':b,'photo_id':record['id'],**extra},404)
    assert album(b)['data']['album']['cover'] is None
    publish(a,0);album(a,404)
    if APACHE:public.request(photos[0]['image_url'].removeprefix(urllib.parse.urlparse(BASE).path),403)
    publish(a,1)
    # Changing album metadata must not change its stable folder or original slug.
    admin.post(f'admin/gallery/edit.php?id={a}',{'csrf_token':csrf,'title':PREFIX+' renamed','description':'Updated','event_date':'2026-09-18','location':'Casablanca','display_order':0,'is_published':1})
    assert album(a)['data']['album']['title'].endswith('renamed')
    assert album(a)['data']['photos'][0]['image_url']==photos[0]['image_url']
    print('PASS album edit/publication, photo ordering/captions/alt, chosen cover, escaping and cross-album ID refusal')
    # Validate real bytes and extension agreement, not multipart Content-Type.
    for filename,body,mime in [('bad.php',png(),'image/png'),('bad.jpg',png(),'image/jpeg'),('bad.svg',b'<svg/>','image/svg+xml'),('bad.webp',b'RIFF0000WEBPbad','image/webp')]:
        result=upload(a,[(filename,body,mime)],422);assert result['success'] is False
    upload(a,[('oversized.jpg',camera+b' '* (15*1024*1024+1-len(camera)),'image/jpeg')],422)
    # Valid PNG header but bomb-sized dimensions: rejected before decoding/allocation.
    ihdr=struct.pack('>IIBBBBB',12001,1,8,2,0,0,0)
    chunk=b'IHDR'+ihdr
    bomb=b'\x89PNG\r\n\x1a\n'+struct.pack('>I',len(ihdr))+chunk+struct.pack('>I',zlib.crc32(chunk))
    upload(a,[('bomb.png',bomb,'image/png')],422)
    assert len(album(a)['data']['photos'])==3
    # Selected cover deletion must remove both files and fall back to the first remaining photo.
    selected=next(p for p in photos if p['id']==second['id'])
    remove_photo(a,second['id'])
    assert album(a)['data']['album']['cover']['id']==record['id']
    if REPO:
        assert not local_image_path(selected['image_url']).exists()
        assert not local_image_path(selected['thumbnail_url']).exists()
    remove_album(a);album(a,404)
    if REPO:assert not (REPO/f'uploads/gallery/album-{a}').exists()
    remove_album(b)
    if REPO:assert not list((REPO/'uploads/gallery').glob('.trash-*'))
    if APACHE:
        for path in ['uploads/.htaccess','admin/gallery/_common.php','admin/gallery/_form.php']:
            public.request(path,403)
        if REPO:
            probe=REPO/'uploads/gallery/test-no-execution.php'
            try:
                probe.write_text('<?php echo "SHOULD_NOT_EXECUTE";')
                public.request('uploads/gallery/test-no-execution.php',403)
            finally:probe.unlink(missing_ok=True)
    print('PASS fake files, limits/bomb protection, cover fallback, database deletion and complete image/directory cleanup')
    for path in ['api/events.php','api/training.php']:
        assert json.loads(public.request(path)[0])['success'] is True
    public.request('api/messages.php',405)
    print('ALL GALLERY INTEGRATION CHECKS PASSED')
finally:
    for album_id in created[:]:
        try:remove_album(album_id)
        except Exception:pass
