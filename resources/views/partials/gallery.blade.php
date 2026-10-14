{{-- Galeri foto + lightbox. $photos = array URL --}}
<div id="lightbox" class="lightbox">
  <span class="lightbox-close" onclick="closeLightbox()">×</span>
  <span class="lightbox-nav lightbox-prev" onclick="prevPhoto()">❮</span>
  <img id="lightboxImage" alt="">
  <span class="lightbox-nav lightbox-next" onclick="nextPhoto()">❯</span>
</div>

@push('scripts')
<script>
let photos = @json($photos);
let currentIndex = 0;

function changePhoto(index){
    currentIndex = index;
    const mainPhoto = document.getElementById('mainPhoto');
    if(mainPhoto && photos[currentIndex]) mainPhoto.src = photos[currentIndex];
    document.querySelectorAll('.thumb-img').forEach(img => img.classList.remove('active-thumb'));
    const active = document.querySelector('.thumb-img[data-index="'+index+'"]');
    if(active) active.classList.add('active-thumb');
}
function openLightbox(index){
    if(!photos.length) return;
    currentIndex = index;
    document.getElementById('lightbox').style.display = 'flex';
    document.getElementById('lightboxImage').src = photos[currentIndex];
}
function closeLightbox(){ document.getElementById('lightbox').style.display = 'none'; }
function nextPhoto(){
    if(!photos.length) return;
    currentIndex = (currentIndex + 1) % photos.length;
    document.getElementById('lightboxImage').src = photos[currentIndex];
    changePhoto(currentIndex);
}
function prevPhoto(){
    if(!photos.length) return;
    currentIndex = (currentIndex - 1 + photos.length) % photos.length;
    document.getElementById('lightboxImage').src = photos[currentIndex];
    changePhoto(currentIndex);
}
document.addEventListener('keydown', function(e){
    if(document.getElementById('lightbox').style.display === 'flex'){
        if(e.key === 'Escape') closeLightbox();
        if(e.key === 'ArrowRight') nextPhoto();
        if(e.key === 'ArrowLeft') prevPhoto();
    }
});
</script>
@endpush
