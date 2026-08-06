FROM wordpress:php8.3-apache

# Larger upload/memory limits than WP defaults, needed for gallery images,
# catalog PDFs and 3D model (.glb/.gltf) uploads.
COPY docker/wp/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
