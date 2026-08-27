/**
 * Past Walks / Routes (and Walks Manager) image gallery lightbox - a small
 * viewer for the {image_grid} thumbnails built by
 * ItemRenderer::buildImageGrid(), and (as of ra.walk.js's addMediaSection())
 * the Walks Manager event photos too.
 *
 * Rather than building its own overlay/backdrop, this plugs into the
 * component's existing modal stacking system (ra.modals/ra.modal in ra.js) -
 * the same one used for search dialogs, GPX downloads, submit forms etc.
 * That means opening a gallery image from inside another modal correctly
 * layers on top of it, and closing the gallery returns to whatever was
 * underneath, exactly like every other modal in the component. Because of
 * this, ra.js must be loaded wherever ra.lightbox.js is (it already is, on
 * every page that also loads ra.walk.js or the front-end Past Walks/Routes
 * views).
 *
 * Markup it works against (already produced server-side, unchanged):
 *   <div class="ra-image-grid">
 *     <a class="ra-image-grid-item" href="{full size image}" title="{caption}">
 *       <img src="{thumbnail}" alt="{caption}">
 *     </a>
 *     ...
 *   </div>
 *
 * Clicking a thumbnail opens the full-size image in a modal with Prev/Next
 * buttons and left/right arrow key navigation (both just update the same
 * modal's content in place - they don't stack a new modal layer per image).
 * Escape closes it via the modal's own close button behaviour. Each
 * .ra-image-grid on the page is its own independent gallery, so a Blog
 * listing showing several items (each with their own gallery) navigates
 * correctly within just the clicked item's photos.
 *
 * If JavaScript is unavailable, the existing <a href="..."> still works as
 * a plain link to the full-size image - this script only intercepts the
 * click, it isn't required for the feature to be usable at all.
 *
 * @since 1.0.0
 */
(function () {
    'use strict';

    function buildViewer(images, startIndex) {
        var container = document.createElement('div');
        container.className = 'ra-lightbox-content';

        var imgEl = document.createElement('img');
        imgEl.className = 'ra-lightbox-image';
        container.appendChild(imgEl);

        var captionEl = document.createElement('div');
        captionEl.className = 'ra-lightbox-caption';
        container.appendChild(captionEl);

        var navEl = document.createElement('div');
        navEl.className = 'ra-lightbox-nav';
        container.appendChild(navEl);

        var prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'link-button tiny mintcake ra-lightbox-prev';
        prevBtn.textContent = '« previous';
        navEl.appendChild(prevBtn);

        var counterEl = document.createElement('span');
        counterEl.className = 'ra-lightbox-counter';
        navEl.appendChild(counterEl);

        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'link-button tiny mintcake ra-lightbox-next';
        nextBtn.textContent = 'next »';
        navEl.appendChild(nextBtn);

        var currentIndex = startIndex;

        function show(index) {
            var total = images.length;
            currentIndex = (index + total) % total;

            var item = images[currentIndex];
            imgEl.src = item.src;
            imgEl.alt = item.caption;
            captionEl.textContent = item.caption;
            counterEl.textContent = (currentIndex + 1) + ' / ' + total;

            var hasMultiple = total > 1;
            prevBtn.style.visibility = hasMultiple ? '' : 'hidden';
            nextBtn.style.visibility = hasMultiple ? '' : 'hidden';
        }

        prevBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            show(currentIndex - 1);
        });
        nextBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            show(currentIndex + 1);
        });

        show(startIndex);

        return {
            element: container,
            show: show,
            getCurrentIndex: function () {
                return currentIndex;
            }
        };
    }

    function collectGalleryImages(grid) {
        var items = grid.querySelectorAll('.ra-image-grid-item');
        var images = [];

        for (var i = 0; i < items.length; i++) {
            images.push({
                src: items[i].getAttribute('href'),
                caption: items[i].getAttribute('title') || ''
            });
        }

        return images;
    }

    function openLightbox(images, startIndex) {
        var wrapper = document.createElement('div');
        var viewer = buildViewer(images, startIndex);
        wrapper.appendChild(viewer.element);

        var modalItem = ra.modals.createModal(wrapper, false);

        function handleKeydown(e) {
            switch (e.key) {
                case 'Escape':
                    modalItem.close();
                    break;
                case 'ArrowLeft':
                    viewer.show(viewer.getCurrentIndex() - 1);
                    break;
                case 'ArrowRight':
                    viewer.show(viewer.getCurrentIndex() + 1);
                    break;
            }
        }

        document.addEventListener('keydown', handleKeydown);

        // ra.modals dispatches this on document whenever any modal in the
        // stack closes - only stop listening once it's specifically this
        // gallery's own modal that closed.
        document.addEventListener('ra-modal-closing', function onClosing(e) {
            if (e.raModal === modalItem) {
                document.removeEventListener('keydown', handleKeydown);
                document.removeEventListener('ra-modal-closing', onClosing);
            }
        });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest('.ra-image-grid-item') : null;

        if (!link) {
            return;
        }

        var grid = link.closest('.ra-image-grid');

        if (!grid) {
            return;
        }

        e.preventDefault();

        var items = grid.querySelectorAll('.ra-image-grid-item');
        var images = collectGalleryImages(grid);
        var index = Array.prototype.indexOf.call(items, link);

        openLightbox(images, index < 0 ? 0 : index);
    });
})();
