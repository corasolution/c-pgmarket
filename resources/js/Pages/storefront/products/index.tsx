import { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Check, ChevronDown, ChevronLeft, ChevronRight, Filter,
    LayoutGrid, List, Search, ShoppingCart, SlidersHorizontal, X,
} from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import HeartButton from '@/Components/HeartButton';
import { useTranslation } from '@/hooks/useTranslation';
import { formatPrice } from '@/lib/utils';
import { thumbUrl, imgFallback } from '@/lib/image';
import type { Category, Product } from '@/types';

interface Paginated<T> {
    data: T[];
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
    q: string;
    category: string;
    min_price: string | null;
    max_price: string | null;
    sort: string;
}

interface Props {
    products: Paginated<Product>;
    categories: Category[];
    filters: Filters;
}

function useSortOptions() {
    const { t } = useTranslation();
    return [
        { value: 'newest',     label: t('product.sort_newest') },
        { value: 'featured',   label: t('product.sort_featured') },
        { value: 'price_asc',  label: t('product.sort_price_asc') },
        { value: 'price_desc', label: t('product.sort_price_desc') },
    ];
}

function ProductCard({ product, view }: { product: Product; view: 'grid' | 'list' }) {
    const { t, localized } = useTranslation();
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

    if (view === 'list') {
        return (
            <Link
                href={route('products.show', product.slug)}
                className="group flex gap-4 overflow-hidden rounded-2xl bg-white p-3 ring-1 ring-gray-100 shadow-sm transition-all hover:shadow-md hover:ring-primary/20"
            >
                <div className="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-gray-50">
                    <div className="absolute right-1 top-1 z-10">
                        <HeartButton productId={product.id} />
                    </div>
                    {product.images?.[0] ? (
                        <img src={thumbUrl(product.images[0])} onError={imgFallback(product.images[0])} alt={localized(product.name_i18n) ?? ''} className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    ) : (
                        <div className="flex h-full items-center justify-center text-3xl">🛍️</div>
                    )}
                </div>
                <div className="flex flex-1 flex-col justify-between py-1">
                    <div>
                        <p className="line-clamp-2 font-semibold text-gray-800 group-hover:text-primary">{localized(product.name_i18n)}</p>
                        {product.shop && <p className="mt-0.5 text-xs text-gray-400">{product.shop.name}</p>}
                        {product.category && <span className="mt-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500">{localized(product.category.name_i18n)}</span>}
                    </div>
                    <div className="flex items-center justify-between">
                        {lowestVariant ? (
                            <span className="text-base font-extrabold text-primary">{formatPrice(lowestVariant.price_cents, lowestVariant.price_currency)}</span>
                        ) : (
                            <span className="text-sm text-gray-300">—</span>
                        )}
                        <button
                            onClick={handleAddToCart}
                            disabled={!lowestVariant || added}
                            title="Add to cart"
                            className={`flex h-7 w-7 items-center justify-center rounded-full text-white shadow-sm transition-all duration-200 disabled:opacity-40 ${
                                added ? 'bg-green-500 scale-110' : 'bg-primary hover:bg-primary/90'
                            }`}
                        >
                            {added ? <Check className="h-3.5 w-3.5" /> : <ShoppingCart className="h-3.5 w-3.5" />}
                        </button>
                    </div>
                </div>
            </Link>
        );
    }

    return (
        <Link
            href={route('products.show', product.slug)}
            className="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-gray-100 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:ring-primary/20"
        >
            <div className="relative aspect-square overflow-hidden bg-gray-50">
                {product.images?.[0] ? (
                    <img src={thumbUrl(product.images[0])} onError={imgFallback(product.images[0])} alt={localized(product.name_i18n) ?? ''} className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                ) : (
                    <div className="flex h-full items-center justify-center bg-linear-to-br from-gray-100 to-gray-200 text-4xl">🛍️</div>
                )}
                {product.is_featured && (
                    <span className="absolute left-2 top-2 rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold uppercase text-white">{t('product.featured_badge')}</span>
                )}
                <div className="absolute right-2 top-2">
                    <HeartButton productId={product.id} />
                </div>
            </div>
            <div className="flex flex-1 flex-col p-3">
                <p className="line-clamp-2 flex-1 text-sm font-semibold leading-snug text-gray-800">{localized(product.name_i18n)}</p>
                {product.shop && <p className="mt-1 truncate text-xs text-gray-400">{product.shop.name}</p>}
                {product.category && <p className="truncate text-[10px] text-gray-300">{localized(product.category.name_i18n)}</p>}
                <div className="mt-2.5 flex items-center justify-between">
                    {lowestVariant ? (
                        <span className="text-sm font-extrabold text-primary">{formatPrice(lowestVariant.price_cents, lowestVariant.price_currency)}</span>
                    ) : (
                        <span className="text-sm text-gray-300">—</span>
                    )}
                    <button
                        onClick={handleAddToCart}
                        disabled={!lowestVariant || added}
                        title="Add to cart"
                        className={`flex h-7 w-7 items-center justify-center rounded-full text-white shadow-sm transition-all duration-200 disabled:opacity-40 ${
                            added ? 'bg-green-500 scale-110' : 'bg-primary hover:bg-primary/90 group-hover:scale-110'
                        }`}
                    >
                        {added ? <Check className="h-3.5 w-3.5" /> : <ShoppingCart className="h-3.5 w-3.5" />}
                    </button>
                </div>
            </div>
        </Link>
    );
}

function Sidebar({
    categories,
    filters,
    onApply,
    onClose,
}: {
    categories: Category[];
    filters: Filters;
    onApply: (f: Partial<Filters>) => void;
    onClose?: () => void;
}) {
    const { t, localized } = useTranslation();
    const [localQ, setLocalQ]       = useState(filters.q);
    const [localCat, setLocalCat]   = useState(filters.category);
    const [localMin, setLocalMin]   = useState(filters.min_price ?? '');
    const [localMax, setLocalMax]   = useState(filters.max_price ?? '');

    function apply() {
        onApply({ q: localQ, category: localCat, min_price: localMin || null, max_price: localMax || null });
        onClose?.();
    }

    function reset() {
        setLocalQ(''); setLocalCat(''); setLocalMin(''); setLocalMax('');
        onApply({ q: '', category: '', min_price: null, max_price: null });
        onClose?.();
    }

    const dirty = localQ !== filters.q || localCat !== filters.category
        || (localMin || null) !== filters.min_price
        || (localMax || null) !== filters.max_price;

    return (
        <div className="flex flex-col gap-5">
            {/* Header */}
            <div className="flex items-center justify-between">
                <span className="flex items-center gap-2 font-bold text-gray-900">
                    <SlidersHorizontal className="h-4 w-4 text-primary" />
                    {t('product.filters')}
                </span>
                {onClose && (
                    <button onClick={onClose} className="rounded-full p-1 hover:bg-gray-100">
                        <X className="h-4 w-4 text-gray-500" />
                    </button>
                )}
            </div>

            {/* Keyword */}
            <div>
                <p className="mb-2 text-xs font-bold uppercase tracking-wide text-gray-400">Search</p>
                <div className="flex overflow-hidden rounded-xl border border-gray-200 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20">
                    <input
                        value={localQ}
                        onChange={(e) => setLocalQ(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && apply()}
                        placeholder="Search products…"
                        className="flex-1 px-3 py-2 text-sm outline-none"
                    />
                    <button onClick={apply} className="px-3 text-gray-400 hover:text-primary">
                        <Search className="h-4 w-4" />
                    </button>
                </div>
            </div>

            {/* Category */}
            <div>
                <p className="mb-2 text-xs font-bold uppercase tracking-wide text-gray-400">Category</p>
                <div className="space-y-1 max-h-56 overflow-y-auto pr-1">
                    <label className="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-50">
                        <input
                            type="radio"
                            name="cat"
                            value=""
                            checked={localCat === ''}
                            onChange={() => setLocalCat('')}
                            className="accent-primary"
                        />
                        <span className={localCat === '' ? 'font-semibold text-primary' : 'text-gray-700'}>All Categories</span>
                    </label>
                    {categories.map((cat) => (
                        <label key={cat.id} className="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-50">
                            <input
                                type="radio"
                                name="cat"
                                value={cat.slug}
                                checked={localCat === cat.slug}
                                onChange={() => setLocalCat(cat.slug)}
                                className="accent-primary"
                            />
                            <span className={localCat === cat.slug ? 'font-semibold text-primary' : 'text-gray-700'}>
                                {localized(cat.name_i18n)}
                            </span>
                        </label>
                    ))}
                </div>
            </div>

            {/* Price Range */}
            <div>
                <p className="mb-2 text-xs font-bold uppercase tracking-wide text-gray-400">Price Range (USD)</p>
                <div className="flex items-center gap-2">
                    <input
                        type="number"
                        min="0"
                        placeholder="Min"
                        value={localMin}
                        onChange={(e) => setLocalMin(e.target.value)}
                        className="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                    <span className="text-gray-300">—</span>
                    <input
                        type="number"
                        min="0"
                        placeholder="Max"
                        value={localMax}
                        onChange={(e) => setLocalMax(e.target.value)}
                        className="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                    />
                </div>
            </div>

            {/* Actions */}
            <div className="flex gap-2">
                <button
                    onClick={apply}
                    disabled={!dirty}
                    className="flex-1 rounded-xl bg-primary py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary/90 disabled:opacity-40"
                >
                    {t('common.apply')}
                </button>
                <button
                    onClick={reset}
                    className="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                >
                    {t('common.reset')}
                </button>
            </div>
        </div>
    );
}

export default function ProductsIndex({ products, categories, filters }: Props) {
    const { t, localized } = useTranslation();
    const sortOptions = useSortOptions();
    const [view, setView]             = useState<'grid' | 'list'>('grid');
    const [drawerOpen, setDrawerOpen] = useState(false);

    function applyFilters(partial: Partial<Filters>) {
        const { ...next } = { ...filters, ...partial };
        const params: Record<string, string> = {};
        for (const [k, v] of Object.entries(next)) {
            if (v != null && v !== '') params[k] = String(v);
        }
        router.get(route('products.index'), params, { preserveState: true, replace: true });
    }

    function applySort(sort: string) {
        applyFilters({ sort });
    }

    const activeFiltersCount = useMemo(() => {
        let n = 0;
        if (filters.q) n++;
        if (filters.category) n++;
        if (filters.min_price) n++;
        if (filters.max_price) n++;
        return n;
    }, [filters]);

    return (
        <StorefrontLayout>
            <Head title="All Products — PG Market" />

            {/* Page header */}
            <div className="bg-white">
                <div className="mx-auto max-w-7xl px-4 py-3">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2 text-sm text-gray-400">
                            <Link href={route('home')} className="hover:text-primary">Home</Link>
                            <ChevronRight className="h-3.5 w-3.5" />
                            <span className="font-semibold text-gray-700">
                                {filters.category
                                    ? (localized(categories.find(c => c.slug === filters.category)?.name_i18n) ?? 'Products')
                                    : filters.q
                                        ? `Results for "${filters.q}"`
                                        : 'All Products'}
                            </span>
                        </div>
                        <span className="text-xs text-gray-400">{products.total} product{products.total !== 1 ? 's' : ''} found</span>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-4 py-8">
                <div className="flex gap-8">

                    {/* Desktop sidebar */}
                    <aside className="hidden w-64 shrink-0 lg:block">
                        <div className="sticky top-24 rounded-2xl bg-white p-5 ring-1 ring-gray-100 shadow-sm">
                            <Sidebar categories={categories} filters={filters} onApply={applyFilters} />
                        </div>
                    </aside>

                    {/* Main content */}
                    <div className="min-w-0 flex-1">

                        {/* Toolbar */}
                        <div className="mb-5 flex flex-wrap items-center gap-3">
                            {/* Mobile filter button */}
                            <button
                                onClick={() => setDrawerOpen(true)}
                                className="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-primary hover:text-primary lg:hidden"
                            >
                                <Filter className="h-4 w-4" />
                                {t('product.filters')}
                                {activeFiltersCount > 0 && (
                                    <span className="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white">
                                        {activeFiltersCount}
                                    </span>
                                )}
                            </button>

                            {/* Active filter chips */}
                            <div className="flex flex-wrap gap-2">
                                {filters.q && (
                                    <span className="flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                        "{filters.q}"
                                        <button onClick={() => applyFilters({ q: '' })}><X className="h-3 w-3" /></button>
                                    </span>
                                )}
                                {filters.category && (
                                    <span className="flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                        {localized(categories.find(c => c.slug === filters.category)?.name_i18n)}
                                        <button onClick={() => applyFilters({ category: '' })}><X className="h-3 w-3" /></button>
                                    </span>
                                )}
                                {filters.min_price && (
                                    <span className="flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                        Min ${filters.min_price}
                                        <button onClick={() => applyFilters({ min_price: null })}><X className="h-3 w-3" /></button>
                                    </span>
                                )}
                                {filters.max_price && (
                                    <span className="flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                        Max ${filters.max_price}
                                        <button onClick={() => applyFilters({ max_price: null })}><X className="h-3 w-3" /></button>
                                    </span>
                                )}
                            </div>

                            <div className="ml-auto flex items-center gap-2">
                                {/* Sort */}
                                <div className="relative">
                                    <select
                                        value={filters.sort}
                                        onChange={(e) => applySort(e.target.value)}
                                        className="appearance-none rounded-xl border border-gray-200 bg-white py-2 pl-3 pr-8 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    >
                                        {sortOptions.map((o) => (
                                            <option key={o.value} value={o.value}>{o.label}</option>
                                        ))}
                                    </select>
                                    <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                                </div>

                                {/* View toggle */}
                                <div className="flex overflow-hidden rounded-xl border border-gray-200 bg-white">
                                    <button
                                        onClick={() => setView('grid')}
                                        className={`p-2 transition ${view === 'grid' ? 'bg-primary text-white' : 'text-gray-400 hover:text-gray-700'}`}
                                        aria-label="Grid view"
                                    >
                                        <LayoutGrid className="h-4 w-4" />
                                    </button>
                                    <button
                                        onClick={() => setView('list')}
                                        className={`p-2 transition ${view === 'list' ? 'bg-primary text-white' : 'text-gray-400 hover:text-gray-700'}`}
                                        aria-label="List view"
                                    >
                                        <List className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Product grid / list */}
                        {products.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center rounded-2xl border bg-white py-24 text-center">
                                <span className="text-6xl">🔍</span>
                                <p className="mt-4 text-lg font-bold text-gray-700">{t('common.no_results')}</p>
                                <p className="mt-1 text-sm text-gray-400">Try adjusting your filters or search term.</p>
                                <button
                                    onClick={() => applyFilters({ q: '', category: '', min_price: null, max_price: null })}
                                    className="mt-5 rounded-full bg-primary px-6 py-2.5 text-sm font-bold text-white transition hover:bg-primary/90"
                                >
                                    {t('common.clear_filters')}
                                </button>
                            </div>
                        ) : view === 'grid' ? (
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                                {products.data.map((p) => <ProductCard key={p.id} product={p} view="grid" />)}
                            </div>
                        ) : (
                            <div className="flex flex-col gap-3">
                                {products.data.map((p) => <ProductCard key={p.id} product={p} view="list" />)}
                            </div>
                        )}

                        {/* Pagination */}
                        {products.last_page > 1 && (
                            <div className="mt-10 flex items-center justify-center gap-2">
                                {products.prev_page_url && (
                                    <Link
                                        href={products.prev_page_url}
                                        className="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-primary hover:text-primary"
                                    >
                                        <ChevronLeft className="h-4 w-4" /> {t('common.prev')}
                                    </Link>
                                )}
                                <span className="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white shadow-sm">
                                    {products.current_page} / {products.last_page}
                                </span>
                                {products.next_page_url && (
                                    <Link
                                        href={products.next_page_url}
                                        className="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-primary hover:text-primary"
                                    >
                                        {t('common.next')} <ChevronRight className="h-4 w-4" />
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Mobile filter drawer */}
            {drawerOpen && (
                <>
                    <div
                        className="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm"
                        onClick={() => setDrawerOpen(false)}
                    />
                    <div className="fixed inset-y-0 left-0 z-50 w-80 overflow-y-auto bg-white p-6 shadow-2xl">
                        <Sidebar
                            categories={categories}
                            filters={filters}
                            onApply={applyFilters}
                            onClose={() => setDrawerOpen(false)}
                        />
                    </div>
                </>
            )}
        </StorefrontLayout>
    );
}
