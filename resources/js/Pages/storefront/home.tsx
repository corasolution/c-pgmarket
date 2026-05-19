import { useCallback, useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Baby, Beef, Book, Building2, Car, Check, ChevronLeft, ChevronRight, Coffee, Cpu, Dog, Dumbbell, Guitar, Hammer, Headphones, Heart, Home as HomeIcon, Laptop, Leaf, Music, Package, Palette, Shirt, ShieldCheck, ShoppingBasket, ShoppingCart, Smartphone, Sparkles, Sprout, Truck, Wrench, RefreshCw, CreditCard, UtensilsCrossed, type LucideIcon } from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import HeartButton from '@/Components/HeartButton';
import { formatPrice } from '@/lib/utils';
import { thumbUrl, imgFallback } from '@/lib/image';
import { useTranslation } from '@/hooks/useTranslation';
import type { Category, Product } from '@/types';

interface DbHeroSlide {
    id: number;
    badge: string | null;
    title: string;
    accent: string | null;
    description: string | null;
    primary_button_label: string | null;
    primary_button_url: string | null;
    secondary_button_label: string | null;
    secondary_button_url: string | null;
    gradient: string;
    sort_order: number;
    is_active: boolean;
}

interface Props {
    categories: Category[];
    featuredProducts: Product[];
    featuredShops: { id: number; name: string; slug: string; logo: string | null; products_count: number }[];
    newArrivals: Product[];
    heroSlides: DbHeroSlide[];
}

const BG_PATTERN = "url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\")"

interface HeroSlide {
    badge: string;
    title: string;
    accent: string;
    description: string;
    primaryLabel: string;
    primaryHref: string;
    secondaryLabel: string;
    secondaryHref: string;
    gradient: string;
}

const FALLBACK_SLIDES: HeroSlide[] = [
    {
        badge: "\uD83C\uDDF0\uD83C\uDDED Cambodia's #1 Multi-Vendor Marketplace",
        title: 'Shop Cambodian',
        accent: 'Local Brands',
        description: 'Discover thousands of products from verified local vendors across Cambodia.',
        primaryLabel: 'Shop Now',
        primaryHref: '/categories/electronics',
        secondaryLabel: 'Start Selling',
        secondaryHref: '/vendor-panel',
        gradient: 'from-secondary via-secondary/90 to-primary/80',
    },
];

function mapSlide(s: DbHeroSlide): HeroSlide {
    return {
        badge: s.badge ?? '',
        title: s.title,
        accent: s.accent ?? '',
        description: s.description ?? '',
        primaryLabel: s.primary_button_label ?? 'Shop Now',
        primaryHref: s.primary_button_url ?? '/products',
        secondaryLabel: s.secondary_button_label ?? 'Browse All',
        secondaryHref: s.secondary_button_url ?? '/products',
        gradient: s.gradient,
    };
}

function HeroSlideshow({ dbSlides }: { dbSlides: DbHeroSlide[] }) {
    const slides: HeroSlide[] = (dbSlides ?? []).length > 0 ? (dbSlides ?? []).map(mapSlide) : FALLBACK_SLIDES;

    const [current, setCurrent] = useState(0);
    const [paused, setPaused] = useState(false);

    const next = useCallback(() => setCurrent((c) => (c + 1) % slides.length), [slides.length]);
    const prev = useCallback(() => setCurrent((c) => (c - 1 + slides.length) % slides.length), [slides.length]);

    useEffect(() => {
        if (paused) return;
        const id = setInterval(next, 5000);
        return () => clearInterval(id);
    }, [paused, next]);

    return (
        <section
            className="relative overflow-hidden"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
        >
            {/* Slides stacked in one grid cell — height driven by tallest slide */}
            <div className="grid">
                {slides.map((slide, i) => (
                    <div
                        key={i}
                        style={{ gridArea: '1 / 1' }}
                        className={`relative bg-linear-to-br ${slide.gradient} py-12 text-white transition-opacity duration-700 ${
                            i === current ? 'opacity-100' : 'opacity-0 pointer-events-none'
                        }`}
                    >
                        <div className="absolute inset-0 opacity-10" style={{ backgroundImage: BG_PATTERN }} />
                        <div className="relative mx-auto max-w-7xl px-4 text-center">
                            <span className="mb-4 inline-block rounded-full bg-white/20 px-4 py-1 text-sm font-medium">
                                {slide.badge}
                            </span>
                            <h1 className="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">
                                {slide.title}<br />
                                <span className="text-accent">{slide.accent}</span>
                            </h1>
                            <p className="mx-auto mt-4 max-w-xl text-lg text-white/80">
                                {slide.description}
                            </p>
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
                                <Link
                                    href={slide.primaryHref}
                                    className="rounded-full bg-primary px-6 py-3 font-semibold text-white shadow hover:bg-primary/90"
                                >
                                    {slide.primaryLabel} <ArrowRight className="ml-1 inline h-4 w-4" />
                                </Link>
                                <Link
                                    href={slide.secondaryHref}
                                    className="rounded-full border border-white/40 px-6 py-3 font-semibold text-white hover:bg-white/10"
                                >
                                    {slide.secondaryLabel}
                                </Link>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Prev / Next arrows */}
            <button
                onClick={prev}
                className="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30"
                aria-label="Previous slide"
            >
                <ChevronLeft className="h-5 w-5" />
            </button>
            <button
                onClick={next}
                className="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-white/20 p-2 text-white backdrop-blur-sm transition hover:bg-white/30"
                aria-label="Next slide"
            >
                <ChevronRight className="h-5 w-5" />
            </button>

            {/* Dot indicators */}
            <div className="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
                {slides.map((_, i) => (
                    <button
                        key={i}
                        onClick={() => setCurrent(i)}
                        className={`h-2 rounded-full transition-all duration-300 ${
                            i === current ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/75'
                        }`}
                        aria-label={`Go to slide ${i + 1}`}
                    />
                ))}
            </div>
        </section>
    );
}

interface CatStyle { Icon: LucideIcon; color: string; bg: string; ring: string }
const CATEGORY_STYLES: Record<string, CatStyle> = {
    // Main categories
    electronics:              { Icon: Cpu,             color: 'text-blue-600',    bg: 'bg-blue-50',    ring: 'group-hover:ring-blue-400'    },
    fashion:                  { Icon: Shirt,           color: 'text-pink-600',    bg: 'bg-pink-50',    ring: 'group-hover:ring-pink-400'    },
    'home-living':            { Icon: HomeIcon,            color: 'text-amber-600',   bg: 'bg-amber-50',   ring: 'group-hover:ring-amber-400'   },
    beauty:                   { Icon: Sparkles,        color: 'text-purple-600',  bg: 'bg-purple-50',  ring: 'group-hover:ring-purple-400'  },
    'beauty-health':          { Icon: Sparkles,        color: 'text-pink-600',    bg: 'bg-pink-50',    ring: 'group-hover:ring-pink-400'    },
    sports:                   { Icon: Dumbbell,        color: 'text-green-600',   bg: 'bg-green-50',   ring: 'group-hover:ring-green-400'   },
    'sports-outdoors':        { Icon: Dumbbell,        color: 'text-green-600',   bg: 'bg-green-50',   ring: 'group-hover:ring-green-400'   },
    food:                     { Icon: UtensilsCrossed, color: 'text-orange-600',  bg: 'bg-orange-50',  ring: 'group-hover:ring-orange-400'  },
    'food-grocery':           { Icon: ShoppingBasket,  color: 'text-green-600',   bg: 'bg-green-50',   ring: 'group-hover:ring-green-400'   },
    beverages:                { Icon: Coffee,          color: 'text-amber-700',   bg: 'bg-amber-50',   ring: 'group-hover:ring-amber-400'   },
    beverage:                 { Icon: Coffee,          color: 'text-amber-700',   bg: 'bg-amber-50',   ring: 'group-hover:ring-amber-400'   },
    'automotive-spare-parts': { Icon: Car,             color: 'text-red-600',     bg: 'bg-red-50',     ring: 'group-hover:ring-red-400'     },
    'car-accessories-spare':  { Icon: Car,             color: 'text-red-600',     bg: 'bg-red-50',     ring: 'group-hover:ring-red-400'     },
    'baby-kids':              { Icon: Baby,            color: 'text-yellow-600',  bg: 'bg-yellow-50',  ring: 'group-hover:ring-yellow-400'  },
    'baby-kid':               { Icon: Baby,            color: 'text-yellow-600',  bg: 'bg-yellow-50',  ring: 'group-hover:ring-yellow-400'  },
    'arts-crafts':            { Icon: Palette,         color: 'text-rose-600',    bg: 'bg-rose-50',    ring: 'group-hover:ring-rose-400'    },
    arts:                     { Icon: Palette,         color: 'text-rose-600',    bg: 'bg-rose-50',    ring: 'group-hover:ring-rose-400'    },
    'building-materials':     { Icon: Building2,       color: 'text-slate-600',   bg: 'bg-slate-100',  ring: 'group-hover:ring-slate-400'   },
    'building-material':      { Icon: Building2,       color: 'text-slate-600',   bg: 'bg-slate-100',  ring: 'group-hover:ring-slate-400'   },
    'tools-hardware':         { Icon: Wrench,          color: 'text-gray-700',    bg: 'bg-gray-100',   ring: 'group-hover:ring-gray-400'    },
    'tools-main':             { Icon: Hammer,          color: 'text-gray-700',    bg: 'bg-gray-100',   ring: 'group-hover:ring-gray-400'    },
    'home-supplies':          { Icon: HomeIcon,            color: 'text-teal-600',    bg: 'bg-teal-50',    ring: 'group-hover:ring-teal-400'    },
    'pet-supplies':           { Icon: Dog,             color: 'text-orange-500',  bg: 'bg-orange-50',  ring: 'group-hover:ring-orange-400'  },
    'agriculture-plants':     { Icon: Sprout,          color: 'text-emerald-600', bg: 'bg-emerald-50', ring: 'group-hover:ring-emerald-400' },
    'agri-plants-pet':        { Icon: Leaf,            color: 'text-emerald-600', bg: 'bg-emerald-50', ring: 'group-hover:ring-emerald-400' },
    'musical-instruments':    { Icon: Music,           color: 'text-violet-600',  bg: 'bg-violet-50',  ring: 'group-hover:ring-violet-400'  },
    'books-stationery':       { Icon: Book,            color: 'text-sky-600',     bg: 'bg-sky-50',     ring: 'group-hover:ring-sky-400'     },
    services:                 { Icon: Truck,           color: 'text-indigo-600',  bg: 'bg-indigo-50',  ring: 'group-hover:ring-indigo-400'  },

    // Sub/legacy categories
    'phone-accessories':      { Icon: Smartphone,      color: 'text-blue-500',    bg: 'bg-blue-50',    ring: 'group-hover:ring-blue-400'    },
    'computer-accessories':   { Icon: Laptop,          color: 'text-indigo-600',  bg: 'bg-indigo-50',  ring: 'group-hover:ring-indigo-400'  },
    fruit:                    { Icon: Leaf,            color: 'text-green-500',   bg: 'bg-green-50',   ring: 'group-hover:ring-green-400'   },
    'kampot-pepper':          { Icon: Leaf,            color: 'text-red-600',     bg: 'bg-red-50',     ring: 'group-hover:ring-red-400'     },
    garlic:                   { Icon: Sprout,          color: 'text-lime-600',    bg: 'bg-lime-50',    ring: 'group-hover:ring-lime-400'    },
    meatball:                 { Icon: Beef,            color: 'text-red-500',     bg: 'bg-red-50',     ring: 'group-hover:ring-red-400'     },
    general:                  { Icon: Package,         color: 'text-gray-500',    bg: 'bg-gray-100',   ring: 'group-hover:ring-gray-300'    },
    'smartphones-tablets':    { Icon: Smartphone,      color: 'text-blue-600',    bg: 'bg-blue-50',    ring: 'group-hover:ring-blue-400'    },
    'laptops-computers':      { Icon: Laptop,          color: 'text-indigo-600',  bg: 'bg-indigo-50',  ring: 'group-hover:ring-indigo-400'  },
    'audio-accessories':      { Icon: Headphones,      color: 'text-cyan-600',    bg: 'bg-cyan-50',    ring: 'group-hover:ring-cyan-400'    },
    furniture:                { Icon: HomeIcon,            color: 'text-amber-700',   bg: 'bg-amber-50',   ring: 'group-hover:ring-amber-400'   },
};
const DEFAULT_CAT_STYLE: CatStyle = { Icon: Package, color: 'text-gray-500', bg: 'bg-gray-100', ring: 'group-hover:ring-gray-300' };

function ProductCard({ product }: { product: Product }) {
    const { localized } = useTranslation();
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
            className="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-gray-100 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:ring-primary/20"
        >
            <div className="relative aspect-square overflow-hidden bg-gray-50">
                {product.images?.[0] ? (
                    <img
                        src={thumbUrl(product.images[0])}
                        onError={imgFallback(product.images[0])}
                        alt={localized(product.name_i18n) ?? ''}
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center bg-linear-to-br from-gray-100 to-gray-200 text-5xl">🛍️</div>
                )}
                {product.is_featured && (
                    <span className="absolute left-2 top-2 rounded-full bg-primary px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">
                        Featured
                    </span>
                )}
                <div className="absolute right-2 top-2">
                    <HeartButton productId={product.id} />
                </div>
                <div className="absolute inset-0 bg-linear-to-t from-black/15 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
            </div>
            <div className="flex flex-1 flex-col p-3.5">
                <p className="line-clamp-2 flex-1 text-sm font-semibold leading-snug text-gray-800">
                    {localized(product.name_i18n)}
                </p>
                {product.shop && (
                    <p className="mt-1.5 truncate text-xs text-gray-400">{product.shop.name}</p>
                )}
                <div className="mt-3 flex items-center justify-between">
                    {lowestVariant ? (
                        <span className="text-base font-extrabold text-primary">
                            {formatPrice(lowestVariant.price_cents, lowestVariant.price_currency)}
                        </span>
                    ) : (
                        <span className="text-sm text-gray-300">—</span>
                    )}
                    <button
                        onClick={handleAddToCart}
                        disabled={!lowestVariant || added}
                        title="Add to cart"
                        className={`flex h-8 w-8 items-center justify-center rounded-full text-white shadow-sm transition-all duration-200 group-hover:scale-110 disabled:opacity-40 ${
                            added ? 'bg-green-500 scale-110' : 'bg-primary hover:bg-primary/90'
                        }`}
                    >
                        {added
                            ? <Check className="h-3.5 w-3.5" />
                            : <ShoppingCart className="h-3.5 w-3.5" />}
                    </button>
                </div>
            </div>
        </Link>
    );
}

const FEATURES = [
    { icon: Truck,       labelKey: 'home.fast_delivery',      descKey: 'home.fast_delivery_desc',      iconBg: 'bg-blue-50',   iconColor: 'text-blue-500'   },
    { icon: ShieldCheck, labelKey: 'home.buyer_protection',   descKey: 'home.buyer_protection_desc',   iconBg: 'bg-green-50',  iconColor: 'text-green-500'  },
    { icon: CreditCard,  labelKey: 'home.secure_payment',     descKey: 'home.secure_payment_desc',     iconBg: 'bg-purple-50', iconColor: 'text-purple-500' },
    { icon: RefreshCw,   labelKey: 'home.easy_returns',       descKey: 'home.easy_returns_desc',       iconBg: 'bg-primary/10',iconColor: 'text-primary'    },
];

export default function Home({ categories, featuredProducts, featuredShops, newArrivals, heroSlides }: Props) {
    const { t, localized } = useTranslation();
    return (
        <StorefrontLayout>
            <Head title="PG Market — Shop Cambodian Brands" />

            {/* Hero Slideshow */}
            <HeroSlideshow dbSlides={heroSlides ?? []} />

            {/* Features strip */}
            <section className="relative -mt-4 mx-auto max-w-5xl px-4">
                <div className="grid grid-cols-2 gap-2 lg:grid-cols-4">
                    {FEATURES.map(({ icon: Icon, labelKey, descKey, iconBg, iconColor }) => (
                        <div
                            key={labelKey}
                            className="group flex items-center gap-2.5 rounded-xl bg-white px-3 py-2.5 shadow-sm ring-1 ring-gray-100 transition-all duration-200 hover:shadow-md hover:ring-primary/20"
                        >
                            <div className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${iconBg} transition-transform duration-200 group-hover:scale-110`}>
                                <Icon className={`h-4 w-4 ${iconColor}`} />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-bold text-gray-900 leading-tight">{t(labelKey)}</p>
                                <p className="text-[10px] text-gray-400 truncate">{t(descKey)}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {/* Categories */}
            {(categories ?? []).length > 0 && (
                <section className="mx-auto mt-12 max-w-7xl px-4">
                    <div className="mb-6 flex items-center justify-between">
                        <h2 className="text-2xl font-bold text-gray-900">{t('home.browse_categories')}</h2>
                        <Link href={route('categories.index')} className="text-sm font-medium text-primary hover:underline">
                            {t('home.view_all')}
                        </Link>
                    </div>
                    <div className="grid grid-cols-4 gap-3 sm:grid-cols-6 lg:grid-cols-8">
                        {categories.map((cat) => {
                            const { Icon, color, bg, ring } = CATEGORY_STYLES[cat.slug] ?? DEFAULT_CAT_STYLE;
                            return (
                                <Link
                                    key={cat.id}
                                    href={route('categories.show', cat.slug)}
                                    className={`group flex flex-col items-center gap-2.5 rounded-2xl border border-gray-100 bg-white px-3 py-4 text-center shadow-sm ring-2 ring-transparent transition-all duration-200 hover:-translate-y-1 hover:shadow-md ${ring}`}
                                >
                                    <div className={`flex h-12 w-12 items-center justify-center rounded-xl ${bg} transition-transform duration-200 group-hover:scale-110`}>
                                        <Icon className={`h-6 w-6 ${color}`} />
                                    </div>
                                    <span className="text-xs font-semibold leading-tight text-gray-700 group-hover:text-gray-900">
                                        {localized(cat.name_i18n)}
                                    </span>
                                </Link>
                            );
                        })}
                    </div>
                </section>
            )}

            {/* Photo Banners */}
            <section className="mx-auto mt-10 max-w-7xl px-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">

                    {/* Main banner — 2/3 width */}
                    <div className="group relative sm:col-span-2 h-44 overflow-hidden rounded-2xl shadow-md">
                        <img
                            src="https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=900&q=80"
                            alt="Kampot Pepper Season"
                            className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                        />
                        <div className="absolute inset-0 bg-linear-to-r from-black/65 via-black/30 to-transparent" />
                        <div className="absolute inset-0 flex items-center px-7">
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-widest text-white/70">Limited Time</p>
                                <h3 className="mt-0.5 text-xl font-extrabold text-white sm:text-2xl">Kampot Pepper Season 🌶️</h3>
                                <p className="mt-1 text-sm text-white/80">Authentic Cambodian spices direct from farms</p>
                                <Link
                                    href={route('categories.show', 'food')}
                                    className="mt-3 inline-block rounded-full bg-white px-5 py-2 text-sm font-bold text-primary shadow transition hover:bg-gray-100"
                                >
                                    {t('home.shop_now')} →
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Side banner — 1/3 width */}
                    <div className="group relative h-44 overflow-hidden rounded-2xl shadow-md">
                        <img
                            src="https://images.unsplash.com/photo-1518770660439-4636190af475?w=600&q=80"
                            alt="Latest Electronics"
                            className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                        />
                        <div className="absolute inset-0 bg-linear-to-t from-black/75 via-black/30 to-transparent" />
                        <div className="absolute inset-0 flex items-end px-5 pb-5">
                            <div>
                                <p className="text-[10px] font-bold uppercase tracking-widest text-white/70">New Arrivals</p>
                                <h3 className="text-base font-extrabold text-white">Latest Electronics</h3>
                                <Link
                                    href={route('categories.show', 'electronics')}
                                    className="mt-2 inline-block rounded-full bg-white/20 px-4 py-1.5 text-xs font-bold text-white backdrop-blur-sm transition hover:bg-white/30"
                                >
                                    Browse →
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            {/* Featured Shops */}
            {(featuredShops ?? []).length > 0 && (
                <section className="mx-auto mt-12 max-w-7xl px-4">
                    <div className="mb-6 flex items-center justify-between">
                        <h2 className="text-2xl font-bold text-gray-900">{t('home.top_shops')}</h2>
                        <Link href={route('shops.index')} className="text-sm font-medium text-primary hover:underline">
                            {t('home.view_all')}
                        </Link>
                    </div>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                        {(featuredShops ?? []).map((shop) => (
                            <Link
                                key={shop.id}
                                href={route('shops.show', shop.slug)}
                                className="group relative flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-primary/20"
                            >
                                {/* Banner */}
                                <div className="h-16 bg-linear-to-br from-secondary/80 to-primary/80" />
                                {/* Logo overlapping the banner */}
                                <div className="absolute left-4 top-7 z-10 h-14 w-14 overflow-hidden rounded-xl border-2 border-white bg-white shadow-md">
                                    {shop.logo ? (
                                        <img src={shop.logo} alt={shop.name} className="h-full w-full object-cover" />
                                    ) : (
                                        <div className="flex h-full w-full items-center justify-center bg-linear-to-br from-primary to-secondary text-xl font-bold text-white">
                                            {shop.name[0]}
                                        </div>
                                    )}
                                </div>
                                <div className="px-4 pb-4 pt-10">
                                    <p className="truncate text-sm font-bold text-gray-900 transition-colors group-hover:text-primary">{shop.name}</p>
                                    <p className="mt-0.5 text-xs text-gray-400">{t('product.products_count', { count: String(shop.products_count) })}</p>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            )}

            {/* Featured Products */}
            {(featuredProducts ?? []).length > 0 && (
                <section className="mx-auto mt-12 max-w-7xl px-4">
                    <div className="mb-6 flex items-center justify-between">
                        <h2 className="text-2xl font-bold text-gray-900">{t('home.featured')}</h2>
                        <Link href={route('products.index')} className="text-sm font-medium text-primary hover:underline">
                            {t('home.view_all')}
                        </Link>
                    </div>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                        {(featuredProducts ?? []).map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                </section>
            )}

            {/* New Coming Products */}
            {(newArrivals ?? []).length > 0 && (
                <section className="mx-auto mt-12 max-w-7xl px-4 pb-16">
                    <div className="mb-6 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex flex-col">
                                <div className="flex items-center gap-2">
                                    <span className="relative flex h-2.5 w-2.5">
                                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75" />
                                        <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-primary" />
                                    </span>
                                    <span className="text-xs font-bold uppercase tracking-widest text-primary">{t('home.just_added')}</span>
                                </div>
                                <h2 className="text-2xl font-bold text-gray-900">{t('home.new_arrivals')}</h2>
                            </div>
                        </div>
                        <Link href={route('products.index', { sort: 'newest' })} className="text-sm font-medium text-primary hover:underline">
                            {t('home.view_all')}
                        </Link>
                    </div>
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                        {(newArrivals ?? []).map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                </section>
            )}
        </StorefrontLayout>
    );
}


