import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import { thumbUrl, imgFallback } from '@/lib/image';
import { useTranslation } from '@/hooks/useTranslation';
import type { Product } from '@/types';

interface Props {
    query: string;
    products: Product[];
}

export default function SearchPage({ query, products }: Props) {
    const { localized } = useTranslation();
    const [q, setQ] = useState(query);

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.visit(`/search?q=${encodeURIComponent(q.trim())}`, { preserveState: true });
    }

    return (
        <StorefrontLayout>
            <Head title={`Search: ${query}`} />
            <div className="mx-auto max-w-7xl px-4 py-8">
                <form onSubmit={handleSearch} className="mb-6">
                    <div className="flex overflow-hidden rounded-full border-2 border-primary">
                        <input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Search products…"
                            className="flex-1 px-5 py-3 text-sm outline-none"
                        />
                        <button type="submit" className="bg-primary px-5 text-white hover:bg-primary-dark">
                            <Search className="h-5 w-5" />
                        </button>
                    </div>
                </form>

                <p className="mb-4 text-sm text-gray-500">
                    {products.length} result{products.length !== 1 ? 's' : ''} for <strong>"{query}"</strong>
                </p>

                {products.length === 0 ? (
                    <div className="rounded-2xl border bg-white py-20 text-center text-gray-500">
                        No products found. Try a different search term.
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        {products.map((product) => {
                            const lowestVariant = product.variants
                                ?.filter((v) => v.is_active)
                                .sort((a, b) => a.price_cents - b.price_cents)[0];
                            return (
                                <Link
                                    key={product.id}
                                    href={route('products.show', product.slug)}
                                    className="group flex flex-col overflow-hidden rounded-xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                                >
                                    <div className="aspect-square overflow-hidden bg-gray-100">
                                        {product.images?.[0] ? (
                                            <img src={thumbUrl(product.images[0])} onError={imgFallback(product.images[0])} alt={localized(product.name_i18n) ?? ''} className="h-full w-full object-cover transition group-hover:scale-105" />
                                        ) : (
                                            <div className="flex h-full items-center justify-center text-4xl">🛍️</div>
                                        )}
                                    </div>
                                    <div className="p-3">
                                        <p className="line-clamp-2 text-sm font-medium text-gray-800">{localized(product.name_i18n)}</p>
                                        {lowestVariant && (
                                            <p className="mt-1 font-bold text-primary">
                                                ${(lowestVariant.price_cents / 100).toFixed(2)}
                                            </p>
                                        )}
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}


