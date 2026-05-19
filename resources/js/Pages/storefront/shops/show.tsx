import { useMemo, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Calendar, Grid3X3, Mail, MapPin, Package, Phone, ShoppingCart, Star } from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import FloatingChat from '@/Components/FloatingChat';
import HeartButton from '@/Components/HeartButton';
import { formatPrice } from '@/lib/utils';
import { thumbUrl, imgFallback } from '@/lib/image';
import type { PageProps } from '@/types';

interface Category {
    id: number;
    slug: string;
    name_i18n: { en?: string };
}

interface Variant {
    id: number;
    price_cents: number;
    price_currency: string;
    is_active: boolean;
}

interface Product {
    id: number;
    name_i18n: { en: string; km?: string };
    slug: string;
    images?: string[];
    is_featured: boolean;
    variants?: Variant[];
    category?: Category;
}

interface Shop {
    id: number;
    name: string;
    slug: string;
    logo: string | null;
    banner: string | null;
    description_i18n: { en?: string; km?: string } | null;
    email: string | null;
    phone: string | null;
    address: Record<string, string> | null;
    products: Product[];
}

interface Stats {
    total_products: number;
    total_reviews: number;
    member_since: string;
}

interface Props {
    shop: Shop;
    stats: Stats;
}

function ProductCard({ product }: { product: Product }) {
    const lowestVariant = product.variants
        ?.filter((v) => v.is_active)
        .sort((a, b) => a.price_cents - b.price_cents)[0];

    return (
        <Link
            href={route('products.show', product.slug)}
            className="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-gray-100 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:ring-primary/20"
        >
            <div className="relative aspect-square overflow-hidden bg-gray-50">
                {product.images?.[0] ? (
                    <img
                        src={thumbUrl(product.images[0])}
                        onError={imgFallback(product.images[0])}
                        alt={product.name_i18n.en}
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center bg-linear-to-br from-gray-100 to-gray-200 text-4xl">🛍️</div>
                )}
                {product.is_featured && (
                    <span className="absolute left-2 top-2 rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">
                        Featured
                    </span>
                )}
                <div className="absolute right-2 top-2">
                    <HeartButton productId={product.id} />
                </div>
                <div className="absolute inset-0 bg-linear-to-t from-black/10 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
            </div>
            <div className="flex flex-1 flex-col p-3">
                <p className="line-clamp-2 flex-1 text-xs font-semibold leading-snug text-gray-800">
                    {product.name_i18n.en}
                </p>
                {product.category && (
                    <p className="mt-1 truncate text-[10px] text-gray-400">{product.category.name_i18n?.en}</p>
                )}
                <div className="mt-2 flex items-center justify-between">
                    {lowestVariant ? (
                        <span className="text-sm font-extrabold text-primary">
                            {formatPrice(lowestVariant.price_cents, lowestVariant.price_currency)}
                        </span>
                    ) : (
                        <span className="text-xs text-gray-300">—</span>
                    )}
                    <span className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white shadow-sm transition-transform duration-200 group-hover:scale-110">
                        <ShoppingCart className="h-3 w-3" />
                    </span>
                </div>
            </div>
        </Link>
    );
}

export default function ShopShow({ shop, stats }: Props) {
    const { auth } = usePage<PageProps>().props;
    const [activeCategory, setActiveCategory] = useState<string | null>(null);

    const categories = useMemo(() => {
        const seen = new Set<string>();
        const cats: { slug: string; name: string; count: number }[] = [];
        shop.products.forEach(p => {
            if (p.category?.slug && !seen.has(p.category.slug)) {
                seen.add(p.category.slug);
                cats.push({
                    slug: p.category.slug,
                    name: p.category.name_i18n?.en ?? p.category.slug,
                    count: shop.products.filter(x => x.category?.slug === p.category!.slug).length,
                });
            }
        });
        return cats;
    }, [shop.products]);

    const filtered = activeCategory
        ? shop.products.filter(p => p.category?.slug === activeCategory)
        : shop.products;

    return (
        <StorefrontLayout>
            <Head title={`${shop.name} — Corasoft`} />

            {/* Banner */}
            <div className="relative h-56 overflow-hidden bg-linear-to-br from-secondary via-secondary/80 to-primary/80">
                {shop.banner && (
                    <img src={shop.banner} alt="" className="h-full w-full object-cover" />
                )}
                <div className="absolute inset-0 bg-linear-to-b from-black/10 via-black/20 to-black/50" />

                {/* Pattern overlay */}
                <div className="absolute inset-0 opacity-5"
                    style={{ backgroundImage: "url(\"data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M20 20.5V18H0v-2h20v-2H0v-2h20v-2H0V8h20V6H0V4h20V2H0V0h22v20h2V0h2v20h2V0h2v20h2V0h2v20h2V0h2v22H20v-1.5zM0 20h2v20H0V20zm4 0h2v20H4V20zm4 0h2v20H8V20zm4 0h2v20h-2V20zm4 0h2v20h-2V20z'/%3E%3C/g%3E%3C/svg%3E\")" }}
                />
            </div>

            {/* Shop info bar */}
            <div className="relative z-10 bg-white border-b shadow-sm">
                <div className="mx-auto max-w-7xl px-4">
                    <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4 py-4">
                        {/* Logo */}
                        <div className="-mt-16 relative z-20 shrink-0 h-24 w-24 overflow-hidden rounded-2xl border-4 border-white bg-white shadow-xl">
                            {shop.logo ? (
                                <img src={shop.logo} alt={shop.name} className="h-full w-full object-cover" />
                            ) : (
                                <div className="flex h-full w-full items-center justify-center bg-linear-to-br from-primary to-secondary text-3xl font-bold text-white">
                                    {shop.name[0]}
                                </div>
                            )}
                        </div>

                        {/* Name + description */}
                        <div className="min-w-0 flex-1">
                            <h1 className="text-xl font-extrabold text-gray-900">{shop.name}</h1>
                            {shop.description_i18n?.en && (
                                <p className="mt-0.5 max-w-xl text-sm text-gray-500 line-clamp-2">{shop.description_i18n.en}</p>
                            )}
                            {/* Contact inline */}
                            <div className="mt-2 flex flex-wrap gap-3 text-xs text-gray-400">
                                {shop.email && (
                                    <span className="flex items-center gap-1"><Mail className="h-3 w-3 text-primary" />{shop.email}</span>
                                )}
                                {shop.phone && (
                                    <span className="flex items-center gap-1"><Phone className="h-3 w-3 text-primary" />{shop.phone}</span>
                                )}
                                {shop.address?.city && (
                                    <span className="flex items-center gap-1"><MapPin className="h-3 w-3 text-primary" />{shop.address.city}, Cambodia</span>
                                )}
                            </div>
                        </div>

                        {/* Stats pills */}
                        <div className="flex shrink-0 gap-3">
                            <div className="flex flex-col items-center rounded-2xl bg-gray-50 px-4 py-2 text-center ring-1 ring-gray-100">
                                <Package className="h-4 w-4 text-primary mb-0.5" />
                                <span className="text-base font-extrabold text-gray-900">{stats.total_products}</span>
                                <span className="text-[10px] text-gray-400">Products</span>
                            </div>
                            <div className="flex flex-col items-center rounded-2xl bg-gray-50 px-4 py-2 text-center ring-1 ring-gray-100">
                                <Star className="h-4 w-4 text-amber-400 mb-0.5" />
                                <span className="text-base font-extrabold text-gray-900">{stats.total_reviews}</span>
                                <span className="text-[10px] text-gray-400">Reviews</span>
                            </div>
                            <div className="flex flex-col items-center rounded-2xl bg-gray-50 px-4 py-2 text-center ring-1 ring-gray-100">
                                <Calendar className="h-4 w-4 text-primary mb-0.5" />
                                <span className="text-base font-extrabold text-gray-900">{stats.member_since}</span>
                                <span className="text-[10px] text-gray-400">Member since</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Products section */}
            <div className="mx-auto max-w-7xl px-4 py-8 pb-16">

                {/* Category filter pills */}
                {categories.length > 0 && (
                    <div className="mb-6 flex items-center gap-2 overflow-x-auto pb-2">
                        <Grid3X3 className="h-4 w-4 shrink-0 text-gray-400" />
                        <button
                            onClick={() => setActiveCategory(null)}
                            className={`shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold transition-all duration-200 ${
                                !activeCategory
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            }`}
                        >
                            All ({shop.products.length})
                        </button>
                        {categories.map(cat => (
                            <button
                                key={cat.slug}
                                onClick={() => setActiveCategory(activeCategory === cat.slug ? null : cat.slug)}
                                className={`shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold transition-all duration-200 ${
                                    activeCategory === cat.slug
                                        ? 'bg-primary text-white shadow-sm'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                }`}
                            >
                                {cat.name} ({cat.count})
                            </button>
                        ))}
                    </div>
                )}

                {/* Product count header */}
                <div className="mb-4 flex items-center justify-between">
                    <p className="text-sm font-semibold text-gray-700">
                        {filtered.length} product{filtered.length !== 1 ? 's' : ''}
                        {activeCategory && <span className="ml-1 font-normal text-gray-400">in this category</span>}
                    </p>
                </div>

                {/* Grid */}
                {filtered.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-24 text-gray-300">
                        <Package className="mb-3 h-14 w-14 opacity-40" />
                        <p className="text-sm">No products in this category yet</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        {filtered.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </div>

            {auth.user && (
                <FloatingChat
                    shopId={shop.id}
                    shopName={shop.name}
                    shopLogo={shop.logo}
                    currentUserId={auth.user.id}
                />
            )}
        </StorefrontLayout>
    );
}
