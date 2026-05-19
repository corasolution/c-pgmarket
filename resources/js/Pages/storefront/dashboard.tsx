import { Head, Link, usePage } from '@inertiajs/react';
import {
    ShoppingBag, Wallet, TrendingUp, Package,
    ArrowRight, User, ShoppingCart, LayoutDashboard, Store,
    Clock, CheckCircle2, XCircle, Truck, ChevronRight, Heart, MapPin,
} from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import { useTranslation } from '@/hooks/useTranslation';
import type { Order } from '@/types';

interface WalletData {
    pending_balance_cents: number;
    available_balance_cents: number;
    lifetime_earned_cents: number;
}

interface Props {
    recentOrders: Order[];
    wallet?: WalletData;
    isVendor: boolean;
}

const STATUS_STYLES: Record<string, { color: string; icon: React.ElementType }> = {
    pending:    { color: 'bg-amber-100 text-amber-700',   icon: Clock },
    processing: { color: 'bg-blue-100 text-blue-700',    icon: Package },
    shipped:    { color: 'bg-indigo-100 text-indigo-700', icon: Truck },
    delivered:  { color: 'bg-green-100 text-green-700',  icon: CheckCircle2 },
    cancelled:  { color: 'bg-red-100 text-red-700',      icon: XCircle },
};

const STATUS_LABEL_KEYS: Record<string, string> = {
    pending: 'common.pending',
    processing: 'common.processing',
    shipped: 'common.shipped',
    delivered: 'common.delivered',
    cancelled: 'common.cancelled',
};

function StatusBadge({ status }: { status: string }) {
    const { t } = useTranslation();
    const style = STATUS_STYLES[status] ?? { color: 'bg-gray-100 text-gray-600', icon: Clock };
    const Icon = style.icon;
    const label = STATUS_LABEL_KEYS[status] ? t(STATUS_LABEL_KEYS[status]) : status;
    return (
        <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ${style.color}`}>
            <Icon className="h-3 w-3" />
            {label}
        </span>
    );
}

export default function Dashboard({ recentOrders, wallet, isVendor }: Props) {
    const { t } = useTranslation();
    const { auth } = usePage().props as { auth: { user: { name: string } } };
    const fmt = (cents: number) => `$${(cents / 100).toFixed(2)}`;
    const firstName = auth.user?.name?.split(' ')[0] ?? 'there';

    const stats = isVendor && wallet
        ? [
            { icon: ShoppingBag, label: t('dashboard.total_orders'),      value: String(recentOrders.length), from: 'from-blue-500',   to: 'to-blue-600' },
            { icon: Wallet,      label: t('dashboard.available_balance'), value: fmt(wallet.available_balance_cents),  from: 'from-emerald-500', to: 'to-emerald-600' },
            { icon: Package,     label: t('dashboard.pending_balance'),   value: fmt(wallet.pending_balance_cents),    from: 'from-orange-500',  to: 'to-orange-600' },
            { icon: TrendingUp,  label: t('dashboard.lifetime'),          value: fmt(wallet.lifetime_earned_cents),   from: 'from-purple-500',  to: 'to-purple-600' },
        ]
        : [
            { icon: ShoppingBag, label: t('dashboard.total_orders'),  value: String(recentOrders.length), from: 'from-blue-500',   to: 'to-blue-600' },
            { icon: TrendingUp,  label: t('dashboard.member_since'),  value: '2026',                      from: 'from-purple-500', to: 'to-purple-600' },
        ];

    const quickLinks = [
        { href: route('orders.index'),    icon: ShoppingBag,     label: t('dashboard.my_orders'),    desc: t('dashboard.my_orders_desc') },
        { href: route('cart.index'),      icon: ShoppingCart,    label: t('dashboard.my_cart'),      desc: t('dashboard.my_cart_desc') },
        { href: route('favorites.index'), icon: Heart,           label: t('dashboard.favorites'),    desc: t('dashboard.favorites_desc') },
        { href: route('addresses.index'), icon: MapPin,          label: t('dashboard.my_addresses'), desc: t('dashboard.my_addresses_desc') },
        { href: route('profile.edit'),    icon: User,            label: t('dashboard.my_profile'),   desc: t('dashboard.my_profile_desc') },
        ...(isVendor ? [{ href: '/vendor-panel', icon: LayoutDashboard, label: t('dashboard.vendor_panel'), desc: t('dashboard.vendor_panel_desc') }] : []),
    ];

    return (
        <StorefrontLayout>
            <Head title="Dashboard" />

            {/* Hero banner */}
            <div className="bg-linear-to-br from-slate-800 via-slate-700 to-slate-900">
                <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                    <p className="text-sm font-medium text-slate-400">{t('dashboard.welcome')}</p>
                    <h1 className="mt-1 text-3xl font-bold tracking-tight text-white">{t('dashboard.greeting', { name: firstName })}</h1>
                    <p className="mt-1 text-sm text-slate-400">{t('dashboard.subtitle')}</p>
                </div>
            </div>

            <div className="mx-auto max-w-6xl px-4 pb-16 sm:px-6">

                {/* Stat cards — overlap the hero */}
                <div className={`-mt-6 mb-8 grid gap-4 ${stats.length === 4 ? 'grid-cols-2 md:grid-cols-4' : 'grid-cols-2'}`}>
                    {stats.map(({ icon: Icon, label, value, from, to }) => (
                        <div key={label} className="rounded-2xl bg-white p-5 shadow-lg ring-1 ring-black/5">
                            <div className={`mb-3 inline-flex rounded-xl bg-gradient-to-br p-2.5 ${from} ${to}`}>
                                <Icon className="h-5 w-5 text-white" />
                            </div>
                            <p className="text-sm text-gray-500">{label}</p>
                            <p className="mt-0.5 text-2xl font-bold text-gray-900">{value}</p>
                        </div>
                    ))}
                </div>

                {/* Quick actions */}
                <div className="mb-8">
                    <h2 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-400">{t('dashboard.quick_actions')}</h2>
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                        {quickLinks.map(({ href, icon: Icon, label, desc }) => (
                            <Link
                                key={label}
                                href={href}
                                className="group flex items-center gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
                            >
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-colors group-hover:bg-slate-800 group-hover:text-white">
                                    <Icon className="h-4 w-4" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold text-gray-800">{label}</p>
                                    <p className="truncate text-xs text-gray-400">{desc}</p>
                                </div>
                                <ChevronRight className="ml-auto h-4 w-4 shrink-0 text-gray-300 transition-transform group-hover:translate-x-0.5 group-hover:text-slate-600" />
                            </Link>
                        ))}
                    </div>
                </div>

                {/* Become a Seller CTA (buyers only) */}
                {!isVendor && (
                    <div className="mb-8">
                        <Link
                            href={route('become-seller')}
                            className="group flex items-center gap-5 rounded-2xl bg-linear-to-r from-primary via-orange-500 to-amber-500 p-6 text-white shadow-lg transition-all hover:-translate-y-0.5 hover:shadow-xl"
                        >
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm">
                                <Store className="h-7 w-7" />
                            </div>
                            <div className="flex-1">
                                <p className="text-lg font-extrabold">Become a Seller</p>
                                <p className="mt-0.5 text-sm text-white/80">Start your own shop on PG Market — zero upfront costs, 8% commission only on sales.</p>
                            </div>
                            <ArrowRight className="h-6 w-6 shrink-0 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>
                )}

                {/* Recent orders */}
                <div>
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-gray-400">{t('dashboard.recent_orders')}</h2>
                        <Link
                            href={route('orders.index')}
                            className="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:text-slate-900"
                        >
                            {t('common.view_all')} <ArrowRight className="h-3 w-3" />
                        </Link>
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        {recentOrders.length === 0 ? (
                            <div className="flex flex-col items-center py-16 text-center">
                                <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                                    <ShoppingBag className="h-7 w-7 text-slate-400" />
                                </div>
                                <p className="font-medium text-gray-700">{t('dashboard.no_orders')}</p>
                                <p className="mt-1 text-sm text-gray-400">{t('dashboard.start_shopping')}</p>
                                <Link
                                    href={route('products.index')}
                                    className="mt-5 inline-flex items-center gap-2 rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700"
                                >
                                    {t('dashboard.browse_products')} <ArrowRight className="h-4 w-4" />
                                </Link>
                            </div>
                        ) : (
                            <div className="divide-y divide-gray-50">
                                {recentOrders.slice(0, 5).map((order) => (
                                    <Link
                                        key={order.id}
                                        href={route('orders.show', order.id)}
                                        className="group flex items-center justify-between px-5 py-4 transition-colors hover:bg-slate-50"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 group-hover:bg-slate-200">
                                                <Package className="h-4 w-4" />
                                            </div>
                                            <div>
                                                <p className="font-mono text-sm font-semibold text-gray-800">
                                                    #{order.reference}
                                                </p>
                                                <StatusBadge status={order.status ?? 'pending'} />
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <p className="text-base font-bold text-gray-900">
                                                ${((order.total_cents ?? 0) / 100).toFixed(2)}
                                            </p>
                                            <ChevronRight className="h-4 w-4 text-gray-300 transition-transform group-hover:translate-x-0.5 group-hover:text-slate-500" />
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}
