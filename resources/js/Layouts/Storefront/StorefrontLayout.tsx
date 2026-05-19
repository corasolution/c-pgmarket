import { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Baby, Beef, Book, Building2, Car, ChevronDown, Coffee, Cpu, Dog, Dumbbell, Hammer, Headphones, Heart, Home, Laptop, LayoutGrid, Leaf, LogOut, MapPin, Menu, Music, Package, Palette, Search, ShoppingBag, ShoppingBasket, ShoppingCart, Shirt, Smartphone, Sparkles, Sprout, Store, Truck, UtensilsCrossed, User, Wrench, X, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';
import type { PageProps } from '@/types';
import ChatbotWidget from '@/Components/ChatbotWidget';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import { useTranslation } from '@/hooks/useTranslation';

interface Props {
    children: ReactNode;
}

interface CatIcon { Icon: LucideIcon; color: string; bg: string }
const DEFAULT_CAT: CatIcon = { Icon: Package, color: 'text-gray-500', bg: 'bg-gray-100' };
const CATEGORY_ICONS: Record<string, CatIcon> = {
    // Main categories
    electronics:              { Icon: Cpu,            color: 'text-blue-600',    bg: 'bg-blue-50'    },
    fashion:                  { Icon: Shirt,          color: 'text-pink-600',    bg: 'bg-pink-50'    },
    'home-living':            { Icon: Home,           color: 'text-amber-600',   bg: 'bg-amber-50'   },
    beauty:                   { Icon: Sparkles,       color: 'text-purple-600',  bg: 'bg-purple-50'  },
    'beauty-health':          { Icon: Sparkles,       color: 'text-purple-600',  bg: 'bg-purple-50'  },
    sports:                   { Icon: Dumbbell,       color: 'text-green-600',   bg: 'bg-green-50'   },
    'sports-outdoors':        { Icon: Dumbbell,       color: 'text-green-600',   bg: 'bg-green-50'   },
    food:                     { Icon: UtensilsCrossed,color: 'text-orange-600',  bg: 'bg-orange-50'  },
    'food-grocery':           { Icon: ShoppingBasket, color: 'text-green-600',   bg: 'bg-green-50'   },
    beverages:                { Icon: Coffee,         color: 'text-amber-700',   bg: 'bg-amber-50'   },
    beverage:                 { Icon: Coffee,         color: 'text-amber-700',   bg: 'bg-amber-50'   },
    'automotive-spare-parts': { Icon: Car,            color: 'text-red-600',     bg: 'bg-red-50'     },
    'car-accessories-spare':  { Icon: Car,            color: 'text-red-600',     bg: 'bg-red-50'     },
    'baby-kids':              { Icon: Baby,           color: 'text-yellow-600',  bg: 'bg-yellow-50'  },
    'baby-kid':               { Icon: Baby,           color: 'text-yellow-600',  bg: 'bg-yellow-50'  },
    'arts-crafts':            { Icon: Palette,        color: 'text-rose-600',    bg: 'bg-rose-50'    },
    arts:                     { Icon: Palette,        color: 'text-rose-600',    bg: 'bg-rose-50'    },
    'building-materials':     { Icon: Building2,      color: 'text-slate-600',   bg: 'bg-slate-100'  },
    'building-material':      { Icon: Building2,      color: 'text-slate-600',   bg: 'bg-slate-100'  },
    'tools-hardware':         { Icon: Wrench,         color: 'text-gray-700',    bg: 'bg-gray-100'   },
    'tools-main':             { Icon: Hammer,         color: 'text-gray-700',    bg: 'bg-gray-100'   },
    'home-supplies':          { Icon: Home,           color: 'text-teal-600',    bg: 'bg-teal-50'    },
    'pet-supplies':           { Icon: Dog,            color: 'text-orange-500',  bg: 'bg-orange-50'  },
    'agriculture-plants':     { Icon: Sprout,         color: 'text-emerald-600', bg: 'bg-emerald-50' },
    'agri-plants-pet':        { Icon: Leaf,           color: 'text-emerald-600', bg: 'bg-emerald-50' },
    'musical-instruments':    { Icon: Music,          color: 'text-violet-600',  bg: 'bg-violet-50'  },
    'books-stationery':       { Icon: Book,           color: 'text-sky-600',     bg: 'bg-sky-50'     },
    services:                 { Icon: Truck,          color: 'text-indigo-600',  bg: 'bg-indigo-50'  },

    // Sub-categories & legacy slugs
    'phone-accessories':      { Icon: Smartphone,     color: 'text-blue-500',    bg: 'bg-blue-50'    },
    'computer-accessories':   { Icon: Laptop,         color: 'text-indigo-600',  bg: 'bg-indigo-50'  },
    'smartphones-tablets':    { Icon: Smartphone,     color: 'text-blue-600',    bg: 'bg-blue-50'    },
    'laptops-computers':      { Icon: Laptop,         color: 'text-indigo-600',  bg: 'bg-indigo-50'  },
    'audio-accessories':      { Icon: Headphones,     color: 'text-cyan-600',    bg: 'bg-cyan-50'    },
    'clothes-shoes':          { Icon: Shirt,          color: 'text-blue-600',    bg: 'bg-blue-50'    },
    furniture:                { Icon: Home,           color: 'text-amber-700',   bg: 'bg-amber-50'   },
    furnitures:               { Icon: Home,           color: 'text-amber-700',   bg: 'bg-amber-50'   },
    fruit:                    { Icon: Leaf,           color: 'text-green-500',   bg: 'bg-green-50'   },
    'kampot-pepper':          { Icon: Leaf,           color: 'text-red-600',     bg: 'bg-red-50'     },
    garlic:                   { Icon: Sprout,         color: 'text-lime-600',    bg: 'bg-lime-50'    },
    meatball:                 { Icon: Beef,           color: 'text-red-500',     bg: 'bg-red-50'     },
    general:                  { Icon: Package,        color: 'text-gray-500',    bg: 'bg-gray-100'   },
};

function NavBar() {
    const { auth, navCategories, cartCount, siteLogo } = usePage<PageProps>().props;
    const user = auth?.user ?? null;
    const { t, localized } = useTranslation();

    const NAV_LINKS = [
        { label: t('nav.home'),     href: () => route('home') },
        { label: t('nav.products'), href: () => route('products.index') },
        { label: t('nav.shops'),    href: () => route('shops.index') },
        { label: t('nav.about'),    href: () => route('about') },
        { label: t('nav.contact'),  href: () => route('contact') },
    ];
    const [menuOpen, setMenuOpen]   = useState(false);
    const [catOpen,  setCatOpen]    = useState(false);
    const [hoveredCatId, setHoveredCatId] = useState<number | null>(null);
    const [userDropdownOpen, setUserDropdownOpen] = useState(false);
    const [query,    setQuery]      = useState('');
    const catRef = useRef<HTMLDivElement>(null);
    const userDropdownRef = useRef<HTMLDivElement>(null);

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        if (query.trim()) router.visit(route('products.index', { q: query.trim() }));
    }

    useEffect(() => {
        function onClickOutside(e: MouseEvent) {
            if (catRef.current && !catRef.current.contains(e.target as Node)) {
                setCatOpen(false);
            }
            if (userDropdownRef.current && !userDropdownRef.current.contains(e.target as Node)) {
                setUserDropdownOpen(false);
            }
        }
        document.addEventListener('mousedown', onClickOutside);
        return () => document.removeEventListener('mousedown', onClickOutside);
    }, []);

    return (
        <header className="sticky top-0 z-50 bg-white border-b border-gray-200">

            {/* ── Row 1: Logo · Search · Actions ── */}
            <div className="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3">

                {/* Logo */}
                <Link href={route('home')} className="flex shrink-0 items-center gap-1">
                    <img
                        src={siteLogo}
                        alt="PG Market"
                        className="h-12 w-12 object-contain"
                        onError={(e) => { (e.target as HTMLImageElement).style.display = 'none'; }}
                    />
                    <span className="text-xl font-extrabold tracking-tight text-secondary">PG Market</span>
                </Link>

                {/* Search — hidden on mobile (shown below) */}
                <form onSubmit={handleSearch} className="hidden flex-1 sm:flex">
                    <div className="flex w-full overflow-hidden rounded-full border-2 border-gray-200 transition-colors focus-within:border-primary">
                        <input
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder={t('nav.search')}
                            className="flex-1 px-4 py-2 text-sm outline-none"
                        />
                        <button type="submit" className="bg-primary px-5 text-white hover:bg-primary-dark transition-colors">
                            <Search className="h-4 w-4" />
                        </button>
                    </div>
                </form>

                {/* Lang + Cart + User */}
                <div className="flex shrink-0 items-center gap-2">
                    <LanguageSwitcher />
                    <Link href={route('cart.index')} className="flex items-center gap-1.5 rounded-full p-2 text-gray-600 hover:bg-gray-100">
                        <div className="relative">
                            <ShoppingCart className="h-5 w-5" />
                            {cartCount > 0 && (
                                <span className="absolute -right-1.5 -top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[9px] font-bold text-white leading-none">
                                    {cartCount > 99 ? '99+' : cartCount}
                                </span>
                            )}
                        </div>
                        <span className="hidden text-sm sm:inline">{t('nav.cart')}</span>
                    </Link>
                    {user ? (
                        <div className="relative" ref={userDropdownRef}>
                            <button
                                onClick={() => setUserDropdownOpen((v) => !v)}
                                className="flex items-center gap-1.5 rounded-full bg-primary-light px-3 py-1.5 text-sm font-semibold text-primary hover:bg-primary/20"
                            >
                                <User className="h-4 w-4" />
                                <span className="hidden sm:inline">{user.name.split(' ')[0]}</span>
                                <ChevronDown className={`hidden h-3 w-3 transition-transform sm:block ${userDropdownOpen ? 'rotate-180' : ''}`} />
                            </button>

                            {userDropdownOpen && (
                                <div className="absolute right-0 z-999 mt-2 w-52 overflow-hidden rounded-xl border border-gray-100 bg-white py-1 shadow-lg">
                                    <div className="border-b px-4 py-2.5">
                                        <p className="text-sm font-semibold text-gray-900">{user.name}</p>
                                        <p className="truncate text-xs text-gray-400">{user.email}</p>
                                    </div>
                                    <Link href={route('dashboard')} className="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" onClick={() => setUserDropdownOpen(false)}>
                                        <LayoutGrid className="h-4 w-4 text-gray-400" /> {t('nav.dashboard')}
                                    </Link>
                                    <Link href={route('orders.index')} className="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" onClick={() => setUserDropdownOpen(false)}>
                                        <ShoppingBag className="h-4 w-4 text-gray-400" /> {t('nav.orders')}
                                    </Link>
                                    <Link href={route('addresses.index')} className="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" onClick={() => setUserDropdownOpen(false)}>
                                        <MapPin className="h-4 w-4 text-gray-400" /> {t('nav.addresses')}
                                    </Link>
                                    <Link href={route('profile.edit')} className="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" onClick={() => setUserDropdownOpen(false)}>
                                        <User className="h-4 w-4 text-gray-400" /> {t('nav.profile')}
                                    </Link>
                                    <div className="border-t">
                                        <button
                                            onClick={() => { setUserDropdownOpen(false); router.post(route('logout')); }}
                                            className="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                        >
                                            <LogOut className="h-4 w-4" /> {t('nav.logout')}
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ) : (
                        <>
                            <Link href={route('login')} className="hidden text-sm text-gray-600 hover:text-primary sm:block">{t('nav.login')}</Link>
                            <Link href={route('register')} className="rounded-full bg-primary px-4 py-1.5 text-sm font-semibold text-white hover:bg-primary-dark">{t('nav.register')}</Link>
                        </>
                    )}
                    <button onClick={() => setMenuOpen((v) => !v)} className="rounded-full p-2 text-gray-600 hover:bg-gray-100 sm:hidden">
                        {menuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                    </button>
                </div>
            </div>

            {/* Mobile search */}
            <div className="border-t px-4 py-2 sm:hidden">
                <form onSubmit={handleSearch}>
                    <div className="flex overflow-hidden rounded-full border border-gray-200">
                        <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder={t('nav.search_mobile')} className="flex-1 px-4 py-2 text-sm outline-none" />
                        <button type="submit" className="bg-primary px-3 text-white"><Search className="h-4 w-4" /></button>
                    </div>
                </form>
            </div>

            {/* ── Row 2: Category dropdown + nav links ── */}
            <nav className="border-t border-gray-100 bg-gray-50">
                <div className="mx-auto flex max-w-7xl items-stretch px-4">

                    {/* Shop Categories dropdown */}
                    <div ref={catRef} className="relative shrink-0">
                        <button
                            onClick={() => setCatOpen((v) => !v)}
                            className={`flex items-center gap-1.5 border-b-2 px-4 py-3 text-sm font-semibold transition-colors ${catOpen ? 'border-primary text-primary' : 'border-transparent text-gray-800 hover:text-primary'}`}
                        >
                            <LayoutGrid className="h-4 w-4" />
                            {t('nav.categories')}
                            <ChevronDown className={`h-3.5 w-3.5 transition-transform duration-200 ${catOpen ? 'rotate-180' : ''}`} />
                        </button>

                        {catOpen && (() => {
                            const hoveredCat = (navCategories ?? []).find(c => c.id === hoveredCatId);
                            const hasChildren = hoveredCat?.children && hoveredCat.children.length > 0;

                            return (
                                <div
                                    className="absolute left-0 top-full z-999 flex rounded-b-2xl border border-t-0 bg-white shadow-2xl"
                                    onMouseLeave={() => setHoveredCatId(null)}
                                >
                                    {/* Left panel — root categories */}
                                    <div className="w-60 shrink-0 border-r" style={{maxHeight:'70vh', overflowY:'auto'}}>
                                        <Link
                                            href={route('categories.index')}
                                            onClick={() => setCatOpen(false)}
                                            className="flex items-center gap-3 px-4 py-3 text-sm font-bold text-secondary hover:bg-gray-50"
                                        >
                                            <LayoutGrid className="h-5 w-5 text-primary" />
                                            {t('nav.all_categories')}
                                        </Link>
                                        <div className="border-t" />
                                        {(navCategories ?? []).map((cat) => {
                                            const { Icon: CatIcon, color, bg } = CATEGORY_ICONS[cat.slug] ?? DEFAULT_CAT;
                                            const isHovered = hoveredCatId === cat.id;
                                            const hasSubs = cat.children && cat.children.length > 0;
                                            return (
                                                <Link
                                                    key={cat.id}
                                                    href={route('categories.show', cat.slug)}
                                                    onClick={() => setCatOpen(false)}
                                                    onMouseEnter={() => setHoveredCatId(cat.id)}
                                                    className={`flex items-center gap-3 px-4 py-2.5 text-sm transition-colors ${isHovered ? 'bg-primary/5 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary'}`}
                                                >
                                                    <div className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-lg ${bg}`}>
                                                        <CatIcon className={`h-4 w-4 ${color}`} />
                                                    </div>
                                                    <span className="flex-1">{localized(cat.name_i18n)}</span>
                                                    {hasSubs && <ChevronDown className={`h-3 w-3 -rotate-90 text-gray-400 ${isHovered ? 'text-primary' : ''}`} />}
                                                </Link>
                                            );
                                        })}
                                    </div>

                                    {/* Right panel — sub-categories (visible on hover) */}
                                    {hasChildren && (
                                        <div className="w-80 border-l bg-gray-50/50 p-5" style={{maxHeight:'70vh', overflowY:'auto'}}>
                                            <div className="mb-4 flex items-center gap-2">
                                                {(() => {
                                                    const { Icon: HIcon, color: hColor, bg: hBg } = CATEGORY_ICONS[hoveredCat?.slug ?? ''] ?? DEFAULT_CAT;
                                                    return (
                                                        <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${hBg}`}>
                                                            <HIcon className={`h-4 w-4 ${hColor}`} />
                                                        </div>
                                                    );
                                                })()}
                                                <h3 className="text-sm font-bold text-gray-900">
                                                    {localized(hoveredCat?.name_i18n)}
                                                </h3>
                                            </div>
                                            <div className="grid grid-cols-2 gap-1.5">
                                                {hoveredCat?.children?.map((sub) => (
                                                    <Link
                                                        key={sub.id}
                                                        href={route('categories.show', sub.slug)}
                                                        onClick={() => setCatOpen(false)}
                                                        className="rounded-lg border border-transparent px-3 py-2 text-[13px] text-gray-600 transition-all hover:border-primary/20 hover:bg-white hover:text-primary hover:shadow-sm"
                                                    >
                                                        {localized(sub.name_i18n)}
                                                    </Link>
                                                ))}
                                            </div>
                                            <Link
                                                href={route('categories.show', hoveredCat?.slug ?? '')}
                                                onClick={() => setCatOpen(false)}
                                                className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
                                            >
                                                {t('home.view_all')}
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            );
                        })()}
                    </div>

                    {/* Regular nav links — scrollable wrapper keeps dropdown unclipped */}
                    <div className="flex items-center overflow-x-auto">
                        {NAV_LINKS.map(({ label, href }) => (
                            <Link
                                key={label}
                                href={href()}
                                className="shrink-0 border-b-2 border-transparent px-4 py-3 text-sm font-medium text-gray-700 transition-colors hover:border-primary hover:text-primary"
                            >
                                {label}
                            </Link>
                        ))}
                    </div>
                </div>
            </nav>

            {/* Mobile menu */}
            {menuOpen && (
                <div className="border-t bg-white px-4 py-3 sm:hidden">
                    <Link href={route('categories.index')} onClick={() => setMenuOpen(false)} className="block py-2 text-sm font-semibold text-gray-800">🗂 {t('nav.all_categories')}</Link>
                    {NAV_LINKS.map(({ label, href }) => (
                        <Link key={label} href={href()} onClick={() => setMenuOpen(false)} className="block py-2 text-sm text-gray-700 hover:text-primary">{label}</Link>
                    ))}
                    {!user && (
                        <Link href={route('login')} onClick={() => setMenuOpen(false)} className="mt-2 block py-2 text-sm text-gray-600">{t('nav.login')}</Link>
                    )}
                </div>
            )}
        </header>
    );
}

export default function StorefrontLayout({ children }: Props) {
    const { siteLogo } = usePage<PageProps>().props;
    const { t } = useTranslation();

    return (
        <div className="min-h-screen bg-gray-50">
            <NavBar />
            <main>{children}</main>
            <ChatbotWidget />
            <footer className="relative mt-16 overflow-hidden bg-[#1565C0] text-white">
                {/* Cambodian skyline silhouette */}
                <div className="pointer-events-none absolute bottom-0 left-0 right-0">
                    <svg viewBox="0 0 1440 180" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg" className="w-full">
                        <path fill="white" fillOpacity="0.07" d="M0 180 L0 158 L40 158 L40 140 L70 140 L70 152 L100 152 L100 130 L130 130 L130 148 L160 148 L160 122 L185 122 L185 138 L215 138 L215 152 L250 152 L250 135 L270 135 L270 88 L276 82 L280 78 L284 82 L290 88 L290 135 L315 135 L315 122 L345 122 L345 135 L375 135 L375 115 L405 115 L405 128 L435 128 L435 148 L460 148 L460 112 L475 112 L475 95 L483 78 L489 62 L492 50 L495 62 L501 78 L508 95 L508 112 L528 112 L540 112 L540 88 L552 68 L557 48 L561 32 L563 18 L565 32 L569 48 L573 68 L585 88 L585 112 L600 112 L600 85 L614 66 L620 46 L624 28 L628 12 L632 4 L636 12 L640 28 L644 46 L650 66 L664 85 L664 112 L679 112 L679 88 L691 68 L696 48 L700 32 L702 18 L704 32 L708 48 L712 68 L724 88 L724 112 L744 112 L754 112 L754 95 L762 78 L768 62 L771 50 L774 62 L780 78 L787 95 L787 112 L804 112 L804 148 L830 148 L830 128 L860 128 L860 115 L890 115 L890 130 L920 130 L920 148 L950 148 L950 135 L970 135 L970 88 L976 82 L980 78 L984 82 L990 88 L990 135 L1015 135 L1015 125 L1045 125 L1045 138 L1075 138 L1075 120 L1100 120 L1100 132 L1130 132 L1130 148 L1165 148 L1165 130 L1195 130 L1195 142 L1225 142 L1225 135 L1255 135 L1255 148 L1290 148 L1290 132 L1320 132 L1320 148 L1360 148 L1360 158 L1400 158 L1400 148 L1440 148 L1440 180 Z"/>
                    </svg>
                </div>

                {/* Decorative plus signs */}
                <span className="pointer-events-none absolute left-[8%] top-6 text-4xl font-thin text-white/10 select-none">+</span>
                <span className="pointer-events-none absolute left-[55%] top-14 text-3xl font-thin text-white/10 select-none">+</span>
                <span className="pointer-events-none absolute right-[12%] top-5 text-4xl font-thin text-white/10 select-none">+</span>

                {/* Main content */}
                <div className="relative mx-auto max-w-7xl px-6 py-14">
                    <div className="grid grid-cols-1 gap-10 md:grid-cols-4">

                        {/* Col 1: Brand */}
                        <div className="flex flex-col items-center text-center md:items-start md:text-left">
                            <div className="mb-4 flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-4 border-white/30 bg-white shadow-xl">
                                <img src={siteLogo} alt="PG Market" className="h-14 w-14 object-contain" />
                            </div>
                            <h3 className="text-xl font-extrabold tracking-wide">PG Market</h3>
                            <p className="mt-2 max-w-[180px] text-sm leading-relaxed text-white/65">
                                {t('footer.tagline')}
                            </p>
                        </div>

                        {/* Col 2: Information */}
                        <div className="text-center md:text-left">
                            <h4 className="text-base font-bold">{t('footer.information')}</h4>
                            <div className="mx-auto mt-1.5 mb-4 h-0.5 w-10 rounded-full bg-white/40 md:mx-0" />
                            <address className="not-italic text-sm leading-7 text-white/75">
                                Phnom Penh, Cambodia<br />
                                Khan Daun Penh District
                            </address>
                            <p className="mt-2 text-sm text-white/75">
                                <span className="font-semibold text-white">Phone: </span>+855 70 85 4444
                            </p>
                            <p className="mt-1 text-sm text-white/75">
                                <span className="font-semibold text-white">Email: </span>contact@corasoft.online
                            </p>
                        </div>

                        {/* Col 3: Quick Links — horizontal orange pills */}
                        <div className="text-center md:text-left">
                            <h4 className="text-base font-bold">{t('footer.quick_links')}</h4>
                            <div className="mx-auto mt-1.5 mb-4 h-0.5 w-10 rounded-full bg-white/40 md:mx-0" />
                            <div className="flex flex-wrap justify-center gap-2 md:justify-start">
                                {([
                                    [t('nav.home'),       () => route('home')],
                                    [t('nav.products'),   () => route('products.index')],
                                    [t('nav.shops'),      () => route('shops.index')],
                                    [t('nav.contact'),    () => route('contact')],
                                    [t('nav.about'),      () => route('about')],
                                ] as [string, () => string][]).map(([label, href]) => (
                                    <Link
                                        key={label}
                                        href={href()}
                                        className="rounded-full bg-orange-500/20 px-3.5 py-1.5 text-xs font-semibold text-orange-300 transition hover:bg-orange-500/40 hover:text-white"
                                    >
                                        {label}
                                    </Link>
                                ))}
                            </div>
                        </div>

                        {/* Col 4: Social Media */}
                        <div className="text-center md:text-left">
                            <h4 className="text-base font-bold">{t('footer.social_media')}</h4>
                            <div className="mx-auto mt-1.5 mb-4 h-0.5 w-10 rounded-full bg-white/40 md:mx-0" />
                            <div className="flex flex-wrap justify-center gap-3 md:justify-start">
                                {([
                                    ['WhatsApp',  '#', 'bg-[#25D366]'],
                                    ['Facebook',  '#', 'bg-[#1877F2]'],
                                    ['Messenger', '#', 'bg-[#0084FF]'],
                                    ['Telegram',  '#', 'bg-[#26A5E4]'],
                                ] as [string, string, string][]).map(([name, href, bg]) => (
                                    <a key={name} href={href} className="flex items-center gap-2 text-sm text-white/75 hover:text-white transition-colors">
                                        <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${bg} text-xs font-bold text-white shadow-md`}>
                                            {name[0]}
                                        </span>
                                        <span className="hidden md:inline">{name}</span>
                                    </a>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Bottom bar */}
                <div className="relative border-t border-white/20">
                    <div className="mx-auto flex max-w-7xl flex-col items-center gap-3 px-6 py-4 text-center text-xs text-white/60 sm:flex-row sm:items-center sm:justify-between sm:text-left">
                        <div>
                            <p>© 2011–{new Date().getFullYear()} PG Market. {t('footer.rights')}</p>
                            <p className="mt-0.5">
                                {t('footer.developed_by')}{' '}
                                <a href="https://www.corasolution.com" target="_blank" rel="noopener noreferrer" className="font-semibold text-white hover:underline">
                                    www.corasolution.com
                                </a>
                            </p>
                        </div>
                        <div className="flex items-center gap-2 sm:shrink-0">
                            <span className="font-medium text-white/70">{t('footer.we_accept')}</span>
                            <span className="rounded px-2.5 py-1 text-[11px] font-extrabold tracking-wider bg-gray-900 text-white border border-gray-700">
                                ABA<span className="text-blue-400">&apos;</span>
                            </span>
                            <span className="rounded px-2.5 py-1 text-[11px] font-extrabold tracking-wider bg-red-600 text-white">
                                KHQR
                            </span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}


