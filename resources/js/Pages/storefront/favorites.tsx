import { Head, Link, router } from '@inertiajs/react';
import { Heart, ShoppingCart, ArrowRight, Check, Package } from 'lucide-react';
import { useState } from 'react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import HeartButton from '@/Components/HeartButton';
import { useTranslation } from '@/hooks/useTranslation';
import { formatPrice } from '@/lib/utils';
import { thumbUrl, imgFallback } from '@/lib/image';
import type { Product } from '@/types';

interface Props {
    products: Product[];
}

function FavoriteCard({ product }: { product: Product }) {
    const lowestVariant = product.variants
        ?.filter((v) => v.is_active)
        .sort((a, b) => a.price_cents - b.price_cents)[0];

    const [added, setAdded] = useState(false);

    function handleAddToCart(e: React.MouseEvent) {
        e.preventDefault();
        e.stopPropagation();
        if (!lowestVariant || added) return;
        setAdded(true);
        router.post(
            route('cart.store'),
            { variant_id: lowestVariant.id, quantity: 1 },
            { preserveScroll: true, preserveState: true, onFinish: () => setTimeout(() => setAdded(false), 1500) },
        );
    }

    return (
        <div className="group relative overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 transition-all hover:-translate-y-0.5 hover:shadow-md">
            <Link href={route('products.show', product.slug)}>
                <div className="relative aspect-square overflow-hidden bg-slate-50">
                    {product.images?.[0] ? (
                        <img
                            src={thumbUrl(product.images[0])}
                            onError={imgFallback(product.images[0])}
                            alt={localized(product.name_i18n) ?? ''}
                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center text-4xl">🛍️</div>
                    )}
                    <div className="absolute right-2 top-2">
                        <HeartButton productId={product.id} />
                    </div>
                </div>

                <div className="p-3">
                    <p className="line-clamp-2 text-sm font-semibold text-gray-800">{localized(product.name_i18n)}</p>
                    {product.shop && (
                        <p className="mt-0.5 truncate text-xs text-gray-400">{product.shop.name}</p>
                    )}
                </div>
            </Link>

            <div className="flex items-center justify-between border-t border-gray-50 px-3 pb-3 pt-2">
                {lowestVariant ? (
                    <span className="text-sm font-extrabold text-primary">
                        {formatPrice(lowestVariant.price_cents, lowestVariant.price_currency)}
                    </span>
                ) : (
                    <span className="text-xs text-gray-300">—</span>
                )}
                <button
                    onClick={handleAddToCart}
                    disabled={!lowestVariant || added}
                    title="Add to cart"
                    className={`flex h-7 w-7 items-center justify-center rounded-full text-white shadow-sm transition-all duration-200 disabled:opacity-40 ${
                        added ? 'scale-110 bg-green-500' : 'bg-primary hover:bg-primary/90 group-hover:scale-110'
                    }`}
                >
                    {added ? <Check className="h-3 w-3" /> : <ShoppingCart className="h-3 w-3" />}
                </button>
            </div>
        </div>
    );
}

export default function Favorites({ products }: Props) {
    const { localized } = useTranslation();
    return (
        <StorefrontLayout>
            <Head title="My Favorites" />

            {/* Hero */}
            <div className="bg-linear-to-br from-slate-800 via-slate-700 to-slate-900">
                <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-red-500/20">
                            <Heart className="h-5 w-5 fill-red-400 text-red-400" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-white">My Favorites</h1>
                            <p className="text-sm text-slate-400">
                                {products.length === 0
                                    ? 'No saved items yet'
                                    : `${products.length} saved item${products.length !== 1 ? 's' : ''}`}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-6xl px-4 pb-16 pt-8 sm:px-6">
                {products.length === 0 ? (
                    <div className="flex flex-col items-center py-20 text-center">
                        <div className="mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-slate-100">
                            <Package className="h-9 w-9 text-slate-300" />
                        </div>
                        <h2 className="text-lg font-semibold text-gray-700">No favorites yet</h2>
                        <p className="mt-1 text-sm text-gray-400">
                            Tap the heart icon on any product to save it here.
                        </p>
                        <Link
                            href={route('products.index')}
                            className="mt-6 inline-flex items-center gap-2 rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700"
                        >
                            Browse Products <ArrowRight className="h-4 w-4" />
                        </Link>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        {products.map((product) => (
                            <FavoriteCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
