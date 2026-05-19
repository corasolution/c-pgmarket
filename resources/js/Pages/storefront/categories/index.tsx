import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Search, Sparkles, ArrowRight, LayoutGrid } from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import { getCategoryMeta } from '@/lib/category-meta';
import { useTranslation } from '@/hooks/useTranslation';
import type { Category } from '@/types';

interface Props {
    categories: Category[];
}

export default function CategoriesIndex({ categories }: Props) {
    const { localized } = useTranslation();
    const [query, setQuery] = useState('');

    const filtered = query.trim()
        ? categories.filter(c => localized(c.name_i18n).toLowerCase().includes(query.toLowerCase()))
        : categories;

    const totalProducts = categories.reduce((s, c) => s + (c.products_count ?? 0), 0);

    return (
        <StorefrontLayout>
            <Head title="All Categories — Corasoft" />

            {/* Hero */}
            <div className="relative overflow-hidden bg-linear-to-br from-secondary via-secondary/90 to-primary/80 py-16 text-white">
                <div className="absolute -right-20 -top-20 h-80 w-80 rounded-full bg-white/5 blur-3xl" />
                <div className="absolute -bottom-12 -left-12 h-64 w-64 rounded-full bg-white/5 blur-2xl" />
                <div className="absolute left-1/3 top-0 h-48 w-48 rounded-full bg-white/5 blur-2xl" />

                <div className="relative mx-auto max-w-7xl px-4 text-center">
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-white/20 px-5 py-1.5 text-sm font-semibold backdrop-blur-sm">
                        <Sparkles className="h-4 w-4" />
                        {categories.length} Categories · {totalProducts.toLocaleString()} Products
                    </div>
                    <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl">Browse All Categories</h1>
                    <p className="mx-auto mt-3 max-w-lg text-lg text-white/75">
                        Discover products from verified Cambodian vendors across every category.
                    </p>

                    {/* Search bar */}
                    <div className="mx-auto mt-8 max-w-md">
                        <div className="relative">
                            <Search className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-white/60" />
                            <input
                                value={query}
                                onChange={e => setQuery(e.target.value)}
                                placeholder="Search categories…"
                                className="w-full rounded-full bg-white/20 py-3 pl-11 pr-5 text-sm text-white placeholder-white/50 backdrop-blur-sm outline-none ring-2 ring-white/30 transition focus:ring-white/60"
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Grid */}
            <section className="mx-auto max-w-7xl px-4 py-12">
                {filtered.length === 0 ? (
                    <div className="py-24 text-center text-gray-400">
                        <LayoutGrid className="mx-auto mb-3 h-10 w-10 opacity-30" />
                        <p className="text-sm">No categories match &ldquo;{query}&rdquo;</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                        {filtered.map(cat => {
                            const m = getCategoryMeta(cat.slug);
                            const hasChildren = (cat.children?.length ?? 0) > 0;
                            return (
                                <div key={cat.id} className="flex flex-col">
                                    <Link
                                        href={route('categories.show', cat.slug)}
                                        className={`group relative flex flex-col items-center overflow-hidden rounded-2xl bg-linear-to-br ${m.from} ${m.to} p-6 text-white shadow-lg transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl ${m.glow}`}
                                    >
                                        {/* Shine overlay on hover */}
                                        <div className="absolute inset-0 bg-linear-to-b from-white/20 via-transparent to-black/10 opacity-0 transition-opacity duration-300 group-hover:opacity-100" />

                                        {/* Emoji bubble */}
                                        <div className="relative mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/25 text-4xl shadow-inner ring-1 ring-white/40 backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                                            {m.emoji}
                                        </div>

                                        {/* Category name */}
                                        <p className="relative z-10 text-center text-sm font-bold leading-tight drop-shadow">
                                            {localized(cat.name_i18n)}
                                        </p>

                                        {/* Product count pill */}
                                        <span className="relative z-10 mt-2 rounded-full bg-white/25 px-3 py-0.5 text-xs font-medium backdrop-blur-sm">
                                            {(cat.products_count ?? 0).toLocaleString()} items
                                        </span>

                                        {/* Arrow appears on hover */}
                                        <ArrowRight className="relative z-10 mt-1.5 h-3.5 w-3.5 opacity-0 transition-all duration-300 group-hover:translate-x-0.5 group-hover:opacity-100" />
                                    </Link>

                                    {/* Sub-category chips */}
                                    {hasChildren && (
                                        <div className="mt-2 flex flex-wrap gap-1">
                                            {cat.children!.slice(0, 5).map(child => (
                                                <Link
                                                    key={child.id}
                                                    href={route('categories.show', child.slug)}
                                                    className="group/chip rounded-full border border-gray-200 bg-white px-2.5 py-0.5 text-[10px] font-medium text-gray-600 shadow-sm transition hover:border-primary hover:text-primary"
                                                    onClick={e => e.stopPropagation()}
                                                >
                                                    {localized(child.name_i18n)}
                                                    {(child.children?.length ?? 0) > 0 && (
                                                        <span className="ml-0.5 text-gray-300 group-hover/chip:text-primary/50">›</span>
                                                    )}
                                                </Link>
                                            ))}
                                            {cat.children!.length > 5 && (
                                                <Link
                                                    href={route('categories.show', cat.slug)}
                                                    className="rounded-full border border-dashed border-gray-300 bg-white px-2.5 py-0.5 text-[10px] font-medium text-gray-400 transition hover:text-primary"
                                                >
                                                    +{cat.children!.length - 5} more
                                                </Link>
                                            )}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </section>
        </StorefrontLayout>
    );
}
