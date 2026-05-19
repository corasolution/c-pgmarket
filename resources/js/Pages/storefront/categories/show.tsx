import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    ShoppingCart,
} from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import HeartButton from '@/Components/HeartButton';
import { formatPrice } from '@/lib/utils';
import { thumbUrl, imgFallback } from '@/lib/image';
import { getCategoryMeta } from '@/lib/category-meta';
import { useTranslation } from '@/hooks/useTranslation';
import type { Brand, Category, Product } from '@/types';

interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface Filters {
    sort: string;
    per_page: number;
    brand: string;
}

interface Props {
    category: Category;
    ancestors: Category[];
    products: PaginatedProducts;
    brands: Brand[];
    selectedBrand: Brand | null;
    filters: Filters;
}

const SORT_OPTIONS: { value: string; label: string }[] = [
    { value: 'newest', label: 'Newest' },
    { value: 'price_asc', label: 'Price: Low to High' },
    { value: 'price_desc', label: 'Price: High to Low' },
    { value: 'featured', label: 'Featured' },
];

const PER_PAGE_OPTIONS = [25, 50, 100];

/* ─── Product card ─────────────────────────────────────────────────── */
function ProductCard({ product }: { product: Product }) {
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
        <Link
            href={route('products.show', product.slug)}
            className="group flex flex-col overflow-hidden rounded-xl border bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
        >
            <div className="relative aspect-square overflow-hidden bg-gray-100">
                {product.images?.[0] ? (
                    <img
                        src={thumbUrl(product.images[0])}
                        onError={imgFallback(product.images[0])}
                        alt={localized(product.name_i18n) ?? ''}
                        className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center text-4xl">🛍️</div>
                )}
                <div className="absolute right-2 top-2">
                    <HeartButton productId={product.id} />
                </div>
            </div>
            <div className="flex flex-1 flex-col p-3">
                <p className="line-clamp-2 flex-1 text-sm font-medium text-gray-800">
                    {localized(product.name_i18n)}
                </p>
                <div className="mt-2 flex items-center justify-between">
                    {lowestVariant ? (
                        <p className="font-bold text-primary">
                            {formatPrice(lowestVariant.price_cents, lowestVariant.price_currency)}
                        </p>
                    ) : (
                        <span />
                    )}
                    <button
                        onClick={handleAddToCart}
                        disabled={!lowestVariant || added}
                        title="Add to cart"
                        className={`flex h-7 w-7 items-center justify-center rounded-full text-white shadow-sm transition-all duration-200 disabled:opacity-40 ${
                            added
                                ? 'scale-110 bg-green-500'
                                : 'bg-primary hover:bg-primary/90 group-hover:scale-110'
                        }`}
                    >
                        {added ? <Check className="h-3 w-3" /> : <ShoppingCart className="h-3 w-3" />}
                    </button>
                </div>
            </div>
        </Link>
    );
}

/* ─── Sub-category pill (pgmarket style) ────────────────────────────── */
function SubCategoryPill({ cat }: { cat: Category }) {
    const m = getCategoryMeta(cat.slug);
    return (
        <Link
            href={route('categories.show', cat.slug)}
            className="group flex min-w-0 items-center gap-2.5 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-primary hover:text-primary hover:shadow-md"
        >
            <span
                className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-linear-to-br ${m.from} ${m.to} text-base shadow-inner ring-1 ring-white/40`}
            >
                {m.emoji}
            </span>
            <span className="truncate leading-tight">{localized(cat.name_i18n)}</span>
        </Link>
    );
}

/* ─── Sub-category section — flex-wrap pills + see more/less ────────── */
const INITIAL_VISIBLE = 11; // leave room for See More pill

function SubCategorySection({ cats }: { cats: Category[] }) {
    const [expanded, setExpanded] = useState(false);
    const canExpand = cats.length > INITIAL_VISIBLE;
    const displayed = expanded || !canExpand ? cats : cats.slice(0, INITIAL_VISIBLE);

    return (
        <div
            className="grid gap-3"
            style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))' }}
        >
            {displayed.map((child) => (
                <SubCategoryPill key={child.id} cat={child} />
            ))}

            {canExpand && !expanded && (
                <button
                    onClick={() => setExpanded(true)}
                    className="flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-500 shadow-sm transition hover:border-primary hover:text-primary"
                >
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <ChevronDown className="h-4 w-4" />
                    </span>
                    See More
                </button>
            )}

            {canExpand && expanded && (
                <button
                    onClick={() => setExpanded(false)}
                    className="flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-500 shadow-sm transition hover:border-primary hover:text-primary"
                >
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <ChevronUp className="h-4 w-4" />
                    </span>
                    See Less
                </button>
            )}
        </div>
    );
}

/* ─── Brand pill ──────────────────────────────────────────────── */
function BrandPill({
    brand,
    active,
    onClick,
}: {
    brand: Brand;
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`flex min-w-0 items-center gap-2.5 rounded-xl border bg-white px-3 py-2 text-sm font-medium shadow-sm transition hover:shadow-md ${
                active
                    ? 'border-primary text-primary ring-2 ring-primary/30'
                    : 'border-gray-200 text-gray-700 hover:border-primary hover:text-primary'
            }`}
        >
            <span className="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-md bg-gray-50 ring-1 ring-gray-100">
                {brand.logo ? (
                    <img
                        src={brand.logo}
                        alt={localized(brand.name_i18n) ?? brand.slug}
                        className="h-full w-full object-contain"
                    />
                ) : (
                    <span className="text-xs font-bold text-gray-400">
                        {(localized(brand.name_i18n) ?? brand.slug).slice(0, 2).toUpperCase()}
                    </span>
                )}
            </span>
            <span className="truncate leading-tight">{localized(brand.name_i18n) ?? brand.slug}</span>
        </button>
    );
}

/* ─── Page ──────────────────────────────────────────────────────────── */
export default function CategoryShow({
    category,
    ancestors,
    products,
    brands,
    selectedBrand,
    filters,
}: Props) {
    const { localized } = useTranslation();
    const { data, current_page, last_page, total, prev_page_url, next_page_url } = products;
    const hasChildren = (category.children?.length ?? 0) > 0;
    const hasBrands = brands.length > 0;

    const updateFilter = (key: 'sort' | 'per_page' | 'brand', value: string | number) => {
        router.get(
            route('categories.show', category.slug),
            { ...filters, [key]: value },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    };

    const toggleBrand = (slug: string) => {
        updateFilter('brand', filters.brand === slug ? '' : slug);
    };

    return (
        <StorefrontLayout>
            <Head title={`${localized(category.name_i18n) ?? 'Category'} — Corasoft`} />

            <div className="mx-auto max-w-7xl px-4 py-6">
                {/* ── Breadcrumb ─────────────────────────────────────── */}
                <nav className="mb-4 flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
                    <Link href={route('home')} className="transition hover:text-primary">Home</Link>
                    <ChevronRight className="h-3.5 w-3.5" />
                    <Link href={route('products.index')} className="transition hover:text-primary">Products</Link>
                    {ancestors.map((anc) => (
                        <span key={anc.id} className="contents">
                            <ChevronRight className="h-3.5 w-3.5" />
                            <Link
                                href={route('categories.show', anc.slug)}
                                className="transition hover:text-primary"
                            >
                                {localized(anc.name_i18n)}
                            </Link>
                        </span>
                    ))}
                    <ChevronRight className="h-3.5 w-3.5" />
                    {selectedBrand ? (
                        <>
                            <Link
                                href={route('categories.show', category.slug)}
                                className="transition hover:text-primary"
                            >
                                {localized(category.name_i18n)}
                            </Link>
                            <ChevronRight className="h-3.5 w-3.5" />
                            <span className="font-semibold text-primary">
                                {selectedBrand.name_i18n?.en ?? selectedBrand.slug}
                            </span>
                        </>
                    ) : (
                        <span className="font-semibold text-primary">{localized(category.name_i18n)}</span>
                    )}
                </nav>

                {/* ── White container: header + subcategories ─────────── */}
                <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h1 className="text-lg font-bold text-gray-900 sm:text-xl">
                            {localized(category.name_i18n)}
                        </h1>

                        <div className="flex items-center gap-2">
                            {/* Sort By */}
                            <div className="relative">
                                <select
                                    value={filters.sort}
                                    onChange={(e) => updateFilter('sort', e.target.value)}
                                    className="appearance-none rounded-lg border border-gray-200 bg-white py-1.5 pl-3 pr-8 text-sm text-gray-700 shadow-sm transition hover:border-primary focus:border-primary focus:outline-none"
                                >
                                    {SORT_OPTIONS.map((opt) => (
                                        <option key={opt.value} value={opt.value}>
                                            Sort By : {opt.label}
                                        </option>
                                    ))}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            </div>

                            {/* Per page */}
                            <div className="relative">
                                <select
                                    value={filters.per_page}
                                    onChange={(e) => updateFilter('per_page', Number(e.target.value))}
                                    className="appearance-none rounded-lg border border-gray-200 bg-white py-1.5 pl-3 pr-8 text-sm text-gray-700 shadow-sm transition hover:border-primary focus:border-primary focus:outline-none"
                                >
                                    {PER_PAGE_OPTIONS.map((n) => (
                                        <option key={n} value={n}>
                                            {n} per page
                                        </option>
                                    ))}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    {hasChildren ? (
                        <SubCategorySection cats={category.children!} />
                    ) : (
                        <p className="py-2 text-sm text-gray-400">No sub-categories.</p>
                    )}

                    {/* Brands row */}
                    {hasBrands && (
                        <>
                            <div className="my-5 h-px bg-gray-100" />
                            <div className="mb-3 flex items-center justify-between">
                                <h2 className="text-sm font-semibold text-gray-700">Brands</h2>
                                {selectedBrand && (
                                    <button
                                        type="button"
                                        onClick={() => updateFilter('brand', '')}
                                        className="text-xs font-medium text-primary hover:underline"
                                    >
                                        Clear brand filter
                                    </button>
                                )}
                            </div>
                            <div
                                className="grid gap-3"
                                style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))' }}
                            >
                                {brands.map((b) => (
                                    <BrandPill
                                        key={b.id}
                                        brand={b}
                                        active={filters.brand === b.slug}
                                        onClick={() => toggleBrand(b.slug)}
                                    />
                                ))}
                            </div>
                        </>
                    )}
                </div>

                {/* ── Products ──────────────────────────────────────── */}
                <section className="mt-6">
                    {data.length === 0 ? (
                        <div className="rounded-2xl border border-gray-200 bg-white py-24 text-center text-gray-400">
                            <p className="text-sm">No products in this category yet.</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            {data.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    )}

                    {last_page > 1 && (
                        <div className="mt-10 flex items-center justify-center gap-3">
                            <button
                                disabled={!prev_page_url}
                                onClick={() => prev_page_url && router.visit(prev_page_url)}
                                className="flex items-center gap-1 rounded-lg border px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <ChevronLeft className="h-4 w-4" /> Previous
                            </button>
                            <span className="text-sm text-gray-500">
                                Page {current_page} of {last_page} · {total.toLocaleString()} items
                            </span>
                            <button
                                disabled={!next_page_url}
                                onClick={() => next_page_url && router.visit(next_page_url)}
                                className="flex items-center gap-1 rounded-lg border px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Next <ChevronRight className="h-4 w-4" />
                            </button>
                        </div>
                    )}
                </section>
            </div>
        </StorefrontLayout>
    );
}
