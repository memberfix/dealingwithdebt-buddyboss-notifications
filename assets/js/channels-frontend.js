(function() {
    'use strict';

    /**
     * Helper function to create DOM elements
     */
    function createElement(tag, attrs, children) {
        const el = document.createElement(tag);
        if (attrs) {
            Object.keys(attrs).forEach(key => {
                if (key === 'class') {
                    el.className = attrs[key];
                } else if (key === 'html') {
                    el.innerHTML = attrs[key];
                } else {
                    el.setAttribute(key, attrs[key]);
                }
            });
        }
        if (children) {
            children.forEach(child => {
                if (child) {
                    el.appendChild(child);
                }
            });
        }
        return el;
    }

    /**
     * Update favorite button text and handler
     */
    function updateFavoriteButton(btn, item) {
        const isFavorited = item.isFavorited;
        btn.innerHTML = isFavorited ? '★ My Favs' : '+ My Favs';
        btn.onclick = (e) => {
            e.preventDefault();
            if (!seriesChannels.isLoggedIn) {
                alert('Please sign in to add favorites.');
                return;
            }
            toggleFavorite(item, btn);
        };
    }

    /**
     * Create hero carousel section (50% width, auto-rotating)
     */
    function createHeroCarousel(title, items) {
        const wrap = createElement('section', { class: 'series-hero-carousel' });
        const carouselWrap = createElement('div', { class: 'series-hero-carousel__wrap' });
        const track = createElement('div', { class: 'series-hero-carousel__track', tabindex: '0' });

        const prev = createElement('button', {
            class: 'series-hero-carousel__nav series-hero-carousel__nav--prev',
            'aria-label': 'Previous'
        });
        prev.innerHTML = '‹';

        const next = createElement('button', {
            class: 'series-hero-carousel__nav series-hero-carousel__nav--next',
            'aria-label': 'Next'
        });
        next.innerHTML = '›';

        let currentIndex = 0;
        let autoRotateInterval;

        items.forEach((item, index) => {
            const slide = createElement('article', { class: 'series-hero-carousel__slide' });
            if (index === 0) slide.classList.add('active');

            const link = createElement('a', {
                href: item.permalink,
                class: 'series-hero-carousel__imgwrap'
            });
            const img = createElement('img', {
                src: item.image || '',
                alt: item.title,
                loading: index === 0 ? 'eager' : 'lazy'
            });
            link.appendChild(img);
            slide.appendChild(link);

            // Create buttons for each slide
            const buttonsWrap = createElement('div', { class: 'series-hero-carousel__buttons' });

            const viewBtn = createElement('button', { class: 'series-hero-carousel__btn series-hero-carousel__btn--primary' });
            viewBtn.innerHTML = '▶ View';
            viewBtn.onclick = () => window.location.href = item.permalink;

            const favBtn = createElement('button', { class: 'series-hero-carousel__btn series-hero-carousel__btn--secondary' });
            updateFavoriteButton(favBtn, item);

            buttonsWrap.appendChild(viewBtn);
            buttonsWrap.appendChild(favBtn);
            slide.appendChild(buttonsWrap);

            track.appendChild(slide);
        });

        function goToSlide(index) {
            const slides = track.querySelectorAll('.series-hero-carousel__slide');
            const dots = wrap.querySelectorAll('.series-hero-carousel__dot');
            slides.forEach(s => s.classList.remove('active'));
            dots.forEach(d => d.classList.remove('active'));
            currentIndex = (index + slides.length) % slides.length;
            slides[currentIndex].classList.add('active');
            if (dots[currentIndex]) {
                dots[currentIndex].classList.add('active');
            }
        }

        function nextSlide() {
            goToSlide(currentIndex + 1);
        }

        function prevSlide() {
            goToSlide(currentIndex - 1);
        }

        function startAutoRotate() {
            if (items.length > 1) {
                const rotationSpeed = seriesChannels.carouselRotationSpeed || 5000;
                autoRotateInterval = setInterval(nextSlide, rotationSpeed);
            }
        }

        function stopAutoRotate() {
            if (autoRotateInterval) {
                clearInterval(autoRotateInterval);
            }
        }

        prev.addEventListener('click', () => {
            prevSlide();
            stopAutoRotate();
            startAutoRotate();
        });

        next.addEventListener('click', () => {
            nextSlide();
            stopAutoRotate();
            startAutoRotate();
        });

        carouselWrap.addEventListener('mouseenter', stopAutoRotate);
        carouselWrap.addEventListener('mouseleave', startAutoRotate);

        carouselWrap.appendChild(prev);
        carouselWrap.appendChild(track);
        carouselWrap.appendChild(next);
        wrap.appendChild(carouselWrap);

        // Create progress dots (only if more than 1 item)
        if (items.length > 1) {
            const dotsWrap = createElement('div', { class: 'series-hero-carousel__dots' });
            items.forEach((_, index) => {
                const dot = createElement('button', {
                    class: 'series-hero-carousel__dot' + (index === 0 ? ' active' : ''),
                    'aria-label': `Go to slide ${index + 1}`
                });
                dot.addEventListener('click', () => {
                    goToSlide(index);
                    stopAutoRotate();
                    startAutoRotate();
                });
                dotsWrap.appendChild(dot);
            });
            wrap.appendChild(dotsWrap);
        }

        startAutoRotate();

        return wrap;
    }

    /**
     * Create row section with navigation arrows
     */
    function createRow(title) {
        const wrap = createElement('section', { class: 'series-row' });
        wrap.appendChild(createElement('h3', { class: 'series-row__title', html: title }));

        const scrollerWrap = createElement('div', { class: 'series-row__scroller-wrap' });
        const scroller = createElement('div', { class: 'series-row__scroller', tabindex: '0' });

        const prevArrow = createElement('button', {
            class: 'series-row__arrow series-row__arrow--prev',
            'aria-label': 'Scroll left'
        });
        prevArrow.innerHTML = '‹';

        const nextArrow = createElement('button', {
            class: 'series-row__arrow series-row__arrow--next',
            'aria-label': 'Scroll right'
        });
        nextArrow.innerHTML = '›';

        function updateArrows() {
            const scrollLeft = scroller.scrollLeft;
            const scrollWidth = scroller.scrollWidth;
            const clientWidth = scroller.clientWidth;

            prevArrow.style.display = scrollLeft > 10 ? 'flex' : 'none';
            nextArrow.style.display = scrollLeft < scrollWidth - clientWidth - 10 ? 'flex' : 'none';
        }

        prevArrow.addEventListener('click', () => {
            scroller.scrollBy({ left: -scroller.clientWidth * 0.8, behavior: 'smooth' });
        });

        nextArrow.addEventListener('click', () => {
            scroller.scrollBy({ left: scroller.clientWidth * 0.8, behavior: 'smooth' });
        });

        scroller.addEventListener('scroll', updateArrows);

        scrollerWrap.appendChild(prevArrow);
        scrollerWrap.appendChild(scroller);
        scrollerWrap.appendChild(nextArrow);
        wrap.appendChild(scrollerWrap);

        setTimeout(updateArrows, 100);

        return { wrap: wrap, scroller: scroller };
    }

    /**
     * Create card for article or series
     */
    function createCard(item) {
        const img = createElement('img', {
            src: item.image || '',
            alt: item.title,
            loading: 'lazy'
        });
        const imageLink = createElement('a', {
            href: item.permalink,
            class: 'series-card__image'
        });
        imageLink.appendChild(img);

        const meta = createElement('div', { class: 'series-card__meta' });
        const titleLink = createElement('a', { href: item.permalink, html: item.title });
        const titleEl = createElement('div', { class: 'series-card__title' });
        titleEl.appendChild(titleLink);
        meta.appendChild(titleEl);

        const favoriteBtn = createElement('button', {
            class: 'series-card__subscribe' + (item.isFavorited ? ' subscribed' : ''),
            'aria-pressed': item.isFavorited ? 'true' : 'false',
            'aria-label': item.isFavorited ? 'Remove from favorites' : 'Add to favorites'
        });
        favoriteBtn.innerHTML = item.isFavorited ? '★' : '☆';

        favoriteBtn.addEventListener('click', e => {
            e.preventDefault();
            if (!seriesChannels.isLoggedIn) {
                alert('Please sign in to add favorites.');
                return;
            }
            toggleFavorite(item, favoriteBtn);
        });

        const wrap = createElement('article', { class: 'series-card' }, [imageLink, meta, favoriteBtn]);
        return wrap;
    }

    /**
     * Toggle favorite status
     */
    function toggleFavorite(item, btn) {
        fetch(seriesChannels.restUrl + 'channels/favorite', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': seriesChannels.nonce
            },
            body: JSON.stringify({
                item_id: item.id,
                item_type: item.type || 'post'
            }),
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                const isFavorited = data.favorited;
                item.isFavorited = isFavorited;

                // Update card button (if it has those classes)
                if (btn.classList.contains('series-card__subscribe')) {
                    btn.setAttribute('aria-pressed', isFavorited ? 'true' : 'false');
                    btn.setAttribute('aria-label', isFavorited ? 'Remove from favorites' : 'Add to favorites');
                    btn.innerHTML = isFavorited ? '★' : '☆';
                    btn.className = 'series-card__subscribe' + (isFavorited ? ' subscribed' : '');
                }

                // Update carousel button (if it has those classes)
                if (btn.classList.contains('series-hero-carousel__btn')) {
                    btn.innerHTML = isFavorited ? '★ My Favs' : '+ My Favs';
                }
            }
        })
        .catch(error => {
            console.error('Favorite error:', error);
        });
    }

    /**
     * Render rows with optional filter
     */
    function renderRows(container, allData, filter = 'series') {
        const rowsContainer = container.querySelector('.series-channels__rows');
        rowsContainer.innerHTML = '';

        if (!allData || allData.length === 0) {
            rowsContainer.innerHTML = '<div class="series-channels__empty">No content available.</div>';
            return;
        }

        // Render featured as hero carousel if present
        const featured = allData.find(row => row.key === 'featured');
        if (featured && featured.items && featured.items.length) {
            let featuredItems = featured.items;
            if (filter === 'articles') {
                featuredItems = featured.items.filter(item => item.type === 'post');
            } else if (filter === 'series') {
                featuredItems = featured.items.filter(item => item.type === 'series');
            }

            if (featuredItems.length > 0) {
                rowsContainer.appendChild(createHeroCarousel(featured.title, featuredItems));
            }
        }

        // Render other rows based on filter
        allData.forEach(rowData => {
            if (rowData.key === 'featured') return;

            if (!rowData.items || rowData.items.length === 0) return;

            let filteredItems = rowData.items;

            // For Articles tab: show posts AND series categories (with posts from those series)
            if (filter === 'articles') {
                if (rowData.key.startsWith('category_')) {
                    // This is a series category - show posts from this series
                    filteredItems = rowData.items.filter(item => item.type === 'post');
                } else {
                    // Regular rows - show only posts
                    filteredItems = rowData.items.filter(item => item.type === 'post');
                }
            } else if (filter === 'series') {
                // Series tab: show only series
                filteredItems = rowData.items.filter(item => item.type === 'series');
            }

            if (filteredItems.length === 0) return;

            const row = createRow(rowData.title);
            filteredItems.forEach(item => {
                row.scroller.appendChild(createCard(item));
            });
            rowsContainer.appendChild(row.wrap);
        });
    }

    /**
     * Initialize channels display
     */
    function init(container) {
        const rows = container.getAttribute('data-rows') || 'featured,popular_articles,popular_series';
        const rowsContainer = container.querySelector('.series-channels__rows');
        const tabs = container.querySelectorAll('.series-channels__tab');
        let allData = null;
        let currentFilter = 'series';

        // Set initial active tab
        tabs.forEach(tab => {
            if (tab.getAttribute('data-filter') === currentFilter) {
                tab.classList.add('series-channels__tab--active');
            } else {
                tab.classList.remove('series-channels__tab--active');
            }
        });

        // Show loading
        rowsContainer.innerHTML = '<div class="series-channels__loading">Loading...</div>';

        // Fetch data
        fetch(seriesChannels.restUrl + 'channels/rows?rows=' + encodeURIComponent(rows), {
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': seriesChannels.nonce
            }
        })
        .then(response => response.json())
        .then(data => {
            allData = data;
            renderRows(container, allData, currentFilter);
        })
        .catch(error => {
            console.error('Error loading channels:', error);
            rowsContainer.innerHTML = '<div class="series-channels__empty">Error loading content.</div>';
        });

        // Tab switching
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const filter = tab.getAttribute('data-filter');
                currentFilter = filter;

                // Update active tab
                tabs.forEach(t => t.classList.remove('series-channels__tab--active'));
                tab.classList.add('series-channels__tab--active');

                // Re-render with filter
                if (allData) {
                    renderRows(container, allData, filter);
                }
            });
        });
    }

    /**
     * Initialize on DOM ready
     */
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.series-channels').forEach(init);
    });

})();
