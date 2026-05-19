/**
 * Product image variant helpers.
 *
 * The backend generates WebP variants for every uploaded product image:
 *   original.webp       — 1200px, 75% quality
 *   original_thumb.webp — 150px,  60% quality (~5-10 KB)
 *   original_md.webp    — 450px,  65% quality (~20-40 KB)
 *   original_lg.webp    — 900px,  70% quality (~60-100 KB)
 */

function variantUrl(url: string | undefined | null, suffix: string): string {
    if (!url) return '';
    const dot = url.lastIndexOf('.');
    if (dot === -1) return url;
    const base = url.substring(0, dot);
    return `${base}${suffix}.webp`;
}

/** 150x150 — product cards, cart items, listing grids */
export function thumbUrl(url: string | undefined | null): string {
    return variantUrl(url, '_thumb');
}

/** 450x450 — product detail main image */
export function mediumUrl(url: string | undefined | null): string {
    return variantUrl(url, '_md');
}

/** 900x900 — lightbox / zoom view */
export function largeUrl(url: string | undefined | null): string {
    return variantUrl(url, '_lg');
}

/**
 * <img> onError handler: falls back to the original URL when a variant is missing.
 * Usage: <img src={thumbUrl(img)} onError={imgFallback(img)} />
 */
export function imgFallback(originalUrl: string | undefined | null) {
    return (e: React.SyntheticEvent<HTMLImageElement>) => {
        const img = e.currentTarget;
        if (originalUrl && img.src !== originalUrl) {
            img.src = originalUrl;
        }
    };
}
