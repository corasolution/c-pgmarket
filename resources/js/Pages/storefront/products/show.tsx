import { useState, useEffect, useCallback } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Minus, Plus, ShoppingCart, Star, Store, Tag, X, ZoomIn, ZoomOut, Zap } from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import HeartButton from '@/Components/HeartButton';
import { useTranslation } from '@/hooks/useTranslation';
import { formatPrice } from '@/lib/utils';
import { mediumUrl, thumbUrl, largeUrl, imgFallback } from '@/lib/image';
import type { Product, ProductVariant } from '@/types';

interface Props {
    product: Product;
    relatedProducts: Product[];
}

export default function ProductShow({ product, relatedProducts }: Props) {
    const { t, localized } = useTranslation();
    const activeVariants = product.variants?.filter((v) => v.is_active) ?? [];
    const [selectedVariant, setSelectedVariant] = useState<ProductVariant | null>(activeVariants[0] ?? null);

    const { post, processing, data, setData } = useForm({
        variant_id: selectedVariant?.id ?? 0,
        quantity: 1,
    });

    const handleVariantSelect = (variant: ProductVariant) => {
        setSelectedVariant(variant);
        setData('variant_id', variant.id);
    };

    const [selectedImage, setSelectedImage] = useState<string | null>(null);
    const mainImage = selectedImage ?? selectedVariant?.image ?? product.images?.[0];

    // Lightbox
    const images = product.images ?? [];
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState(0);
    const [zoom, setZoom] = useState(1);

    const openLightbox = useCallback(() => {
        const idx = images.indexOf(mainImage ?? '');
        setLightboxIndex(idx >= 0 ? idx : 0);
        setZoom(1);
        setLightboxOpen(true);
    }, [mainImage, images]);

    const closeLightbox = () => { setLightboxOpen(false); setZoom(1); };
    const prevImage = () => { setLightboxIndex((i) => (i - 1 + images.length) % images.length); setZoom(1); };
    const nextImage = () => { setLightboxIndex((i) => (i + 1) % images.length); setZoom(1); };

    useEffect(() => {
        if (!lightboxOpen) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevImage();
            if (e.key === 'ArrowRight') nextImage();
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [lightboxOpen]);

    return (
        <StorefrontLayout>
            <Head title={`${localized(product.name_i18n) ?? ''} — Corasoft`} />

            {/* Breadcrumb */}
            <div className="bg-gray-50">
                <div className="mx-auto flex max-w-7xl items-center gap-1 px-4 py-2.5 text-xs text-gray-400">
                    <Link href={route('home')} className="hover:text-primary transition-colors">{t('nav.home')}</Link>
                    <ChevronRight className="h-3 w-3" />
                    {product.category && (
                        <>
                            <Link href={route('categories.show', product.category.slug)} className="hover:text-primary transition-colors">
                                {localized(product.category?.name_i18n)}
                            </Link>
                            <ChevronRight className="h-3 w-3" />
                        </>
                    )}
                    <span className="max-w-[200px] truncate text-gray-600">{localized(product.name_i18n)}</span>
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-4 py-8">
                <div className="grid grid-cols-1 gap-8 lg:grid-cols-[420px_1fr]">

                    {/* Image panel */}
                    <div className="space-y-3">
                        <div
                            className="group relative aspect-square cursor-zoom-in overflow-hidden rounded-2xl bg-gray-50 ring-1 ring-gray-100 shadow-sm"
                            onClick={mainImage ? openLightbox : undefined}
                        >
                            {mainImage ? (
                                <>
                                    <img src={mediumUrl(mainImage)} onError={imgFallback(mainImage)} alt={localized(product.name_i18n) ?? ''} className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                    <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <div className="rounded-full bg-black/40 p-2 backdrop-blur-sm">
                                            <ZoomIn className="h-6 w-6 text-white" />
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <div className="flex h-full flex-col items-center justify-center gap-3 text-gray-300">
                                    <ShoppingCart className="h-20 w-20" />
                                    <span className="text-sm font-medium">No image available</span>
                                </div>
                            )}
                            {/* Heart button — top-right of image */}
                            <div className="absolute right-3 top-3 z-10" onClick={(e) => e.stopPropagation()}>
                                <HeartButton productId={product.id} size="lg" />
                            </div>
                        </div>
                        {product.images && product.images.length > 1 && (
                            <div className="flex gap-2 overflow-x-auto pb-1">
                                {product.images.map((img, i) => (
                                    <button
                                        key={i}
                                        onClick={() => setSelectedImage(img)}
                                        className={`h-16 w-16 shrink-0 overflow-hidden rounded-xl ring-2 transition-all ${
                                            mainImage === img ? 'ring-primary' : 'ring-gray-200 hover:ring-primary/50'
                                        }`}
                                    >
                                        <img src={thumbUrl(img)} onError={imgFallback(img)} alt="" className="h-full w-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Details panel */}
                    <div className="flex flex-col">
                        {/* Shop */}
                        {product.shop && (
                            <Link
                                href={route('shops.show', product.shop.slug)}
                                className="mb-3 flex w-fit items-center gap-2 rounded-full bg-gray-100 px-4 py-1.5 text-sm font-semibold text-gray-700 hover:bg-primary/10 hover:text-primary transition-colors"
                            >
                                <Store className="h-4 w-4" />
                                {product.shop.name}
                            </Link>
                        )}

                        <h1 className="text-2xl font-extrabold leading-snug text-gray-900 sm:text-3xl">
                            {localized(product.name_i18n)}
                        </h1>

                        {/* Category badge */}
                        {product.category && (
                            <Link
                                href={route('categories.show', product.category.slug)}
                                className="mt-2 flex w-fit items-center gap-1 text-xs text-gray-400 hover:text-primary transition-colors"
                            >
                                <Tag className="h-3 w-3" />
                                {localized(product.category?.name_i18n)}
                            </Link>
                        )}

                        {/* Mock stars */}
                        <div className="mt-3 flex items-center gap-1">
                            {[1,2,3,4,5].map(s => (
                                <Star key={s} className="h-4 w-4 fill-amber-400 text-amber-400" />
                            ))}
                            <span className="ml-1 text-xs text-gray-400">{t('product.verified')}</span>
                        </div>

                        {/* Price */}
                        <div className="mt-5 flex items-baseline gap-3">
                            <span className="text-4xl font-extrabold text-primary">
                                {selectedVariant
                                    ? formatPrice(selectedVariant.price_cents, selectedVariant.price_currency)
                                    : activeVariants[0]
                                        ? formatPrice(activeVariants[0].price_cents, activeVariants[0].price_currency)
                                        : '—'}
                            </span>
                        </div>

                        <div className="my-5 border-t border-dashed border-gray-200" />

                        {/* Variant picker */}
                        {activeVariants.length > 1 && (
                            <div className="mb-5">
                                <p className="mb-2.5 text-sm font-semibold text-gray-700">{t('product.options')}</p>
                                <div className="flex flex-wrap gap-2">
                                    {activeVariants.map((v) => (
                                        <button
                                            key={v.id}
                                            onClick={() => handleVariantSelect(v)}
                                            className={`rounded-xl border-2 px-4 py-2 text-sm font-medium transition-all duration-200 ${
                                                selectedVariant?.id === v.id
                                                    ? 'border-primary bg-primary/10 text-primary shadow-sm'
                                                    : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'
                                            }`}
                                        >
                                            {Object.values(v.options ?? {}).join(' / ') || v.sku}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Quantity stepper */}
                        <div className="mb-5">
                            <p className="mb-2.5 text-sm font-semibold text-gray-700">{t('product.quantity')}</p>
                            <div className="flex items-center gap-3">
                                <div className="flex items-center overflow-hidden rounded-xl border border-gray-200">
                                    <button
                                        type="button"
                                        onClick={() => setData('quantity', Math.max(1, data.quantity - 1))}
                                        className="flex h-10 w-10 items-center justify-center text-gray-600 hover:bg-gray-50 transition-colors"
                                    >
                                        <Minus className="h-4 w-4" />
                                    </button>
                                    <span className="w-12 text-center text-base font-bold text-gray-900">{data.quantity}</span>
                                    <button
                                        type="button"
                                        onClick={() => setData('quantity', Math.min(99, data.quantity + 1))}
                                        className="flex h-10 w-10 items-center justify-center text-gray-600 hover:bg-gray-50 transition-colors"
                                    >
                                        <Plus className="h-4 w-4" />
                                    </button>
                                </div>
                                {selectedVariant && product.stock_track && (
                                    <span className="text-xs text-gray-400">{selectedVariant.stock_quantity} {t('product.in_stock')}</span>
                                )}
                            </div>
                        </div>

                        {/* Buttons row */}
                        <div className="flex gap-3">
                            <button
                                onClick={() => post(route('cart.store'))}
                                disabled={processing || !selectedVariant || (product.stock_track && selectedVariant.stock_quantity === 0)}
                                className="flex flex-1 items-center justify-center gap-2 rounded-2xl border-2 border-primary bg-white px-6 py-3.5 text-sm font-bold text-primary shadow-sm transition hover:bg-primary/5 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <ShoppingCart className="h-4 w-4" />
                                {product.stock_track && selectedVariant?.stock_quantity === 0 ? t('product.out_of_stock') : t('product.add_to_cart')}
                            </button>

                            <button
                                onClick={() => {
                                    post(route('cart.store'), {
                                        onSuccess: () => { router.visit(route('checkout.index')); },
                                    });
                                }}
                                disabled={processing || !selectedVariant || (product.stock_track && selectedVariant.stock_quantity === 0)}
                                className="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary/25 transition hover:bg-primary/90 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Zap className="h-4 w-4" />
                                {t('product.buy_now')}
                            </button>

                        </div>

                        {/* Description */}
                        {localized(product.description_i18n) && (
                            <div className="mt-6 rounded-2xl bg-gray-50 p-5">
                                <h2 className="mb-2 text-sm font-bold text-gray-900">{t('product.description')}</h2>
                                <p className="text-sm leading-relaxed text-gray-600">{localized(product.description_i18n)}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Related Products */}
                {relatedProducts.length > 0 && (
                    <section className="mt-16">
                        <div className="mb-6 flex items-center justify-between">
                            <div>
                                <h2 className="text-xl font-extrabold text-gray-900">You May Also Like</h2>
                                <p className="mt-0.5 text-sm text-gray-400">More from {localized(product.category?.name_i18n)}</p>
                            </div>
                            {product.category && (
                                <Link href={route('categories.show', product.category.slug)} className="text-sm font-semibold text-primary hover:underline">
                                    View all →
                                </Link>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            {relatedProducts.map(rel => {
                                const v = rel.variants
                                    ?.filter(x => x.is_active)
                                    .sort((a, b) => a.price_cents - b.price_cents)[0];
                                return (
                                    <Link
                                        key={rel.id}
                                        href={route('products.show', rel.slug)}
                                        className="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-gray-100 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:ring-primary/20"
                                    >
                                        <div className="relative aspect-square overflow-hidden bg-gray-50">
                                            {rel.images?.[0] ? (
                                                <img
                                                    src={thumbUrl(rel.images[0])}
                                                    onError={imgFallback(rel.images[0])}
                                                    alt={localized(rel.name_i18n) ?? ''}
                                                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                            ) : (
                                                <div className="flex h-full items-center justify-center bg-linear-to-br from-gray-100 to-gray-200 text-4xl">🛍️</div>
                                            )}
                                        </div>
                                        <div className="p-3">
                                            <p className="line-clamp-2 text-xs font-semibold leading-snug text-gray-800">{localized(rel.name_i18n)}</p>
                                            {v && (
                                                <p className="mt-1.5 text-sm font-extrabold text-primary">
                                                    {formatPrice(v.price_cents, v.price_currency)}
                                                </p>
                                            )}
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>
                    </section>
                )}
            </div>
            {/* Lightbox */}
            {lightboxOpen && images.length > 0 && (
                <div
                    className="fixed inset-0 z-[1000] flex items-center justify-center bg-black/90 backdrop-blur-sm"
                    onClick={closeLightbox}
                >
                    {/* Close */}
                    <button onClick={closeLightbox} className="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20 transition">
                        <X className="h-6 w-6" />
                    </button>

                    {/* Zoom controls */}
                    <div className="absolute right-4 top-16 flex flex-col gap-2">
                        <button onClick={(e) => { e.stopPropagation(); setZoom((z) => Math.min(z + 0.5, 4)); }} className="rounded-full bg-white/10 p-2 text-white hover:bg-white/20 transition">
                            <ZoomIn className="h-5 w-5" />
                        </button>
                        <button onClick={(e) => { e.stopPropagation(); setZoom((z) => Math.max(z - 0.5, 1)); }} className="rounded-full bg-white/10 p-2 text-white hover:bg-white/20 transition">
                            <ZoomOut className="h-5 w-5" />
                        </button>
                        <span className="text-center text-xs text-white/60">{Math.round(zoom * 100)}%</span>
                    </div>

                    {/* Prev */}
                    {images.length > 1 && (
                        <button onClick={(e) => { e.stopPropagation(); prevImage(); }} className="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20 transition">
                            <ChevronLeft className="h-6 w-6" />
                        </button>
                    )}

                    {/* Image */}
                    <div className="overflow-hidden" onClick={(e) => e.stopPropagation()}>
                        <img
                            src={largeUrl(images[lightboxIndex])}
                            onError={imgFallback(images[lightboxIndex])}
                            alt=""
                            style={{ transform: `scale(${zoom})`, transition: 'transform 0.2s ease' }}
                            className="max-h-[85vh] max-w-[85vw] rounded-xl object-contain"
                        />
                    </div>

                    {/* Next */}
                    {images.length > 1 && (
                        <button onClick={(e) => { e.stopPropagation(); nextImage(); }} className="absolute right-16 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20 transition">
                            <ChevronRight className="h-6 w-6" />
                        </button>
                    )}

                    {/* Dots */}
                    {images.length > 1 && (
                        <div className="absolute bottom-4 flex gap-2">
                            {images.map((_, i) => (
                                <button key={i} onClick={(e) => { e.stopPropagation(); setLightboxIndex(i); setZoom(1); }}
                                    className={`h-2 rounded-full transition-all ${i === lightboxIndex ? 'w-6 bg-white' : 'w-2 bg-white/40 hover:bg-white/70'}`}
                                />
                            ))}
                        </div>
                    )}
                </div>
            )}
        </StorefrontLayout>
    );
}
