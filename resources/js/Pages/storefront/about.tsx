import { Head, Link } from '@inertiajs/react';
import { ShieldCheck, Truck, Users, Store, Star, Package, ArrowRight } from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';

const STATS = [
    { value: '500+', label: 'Verified Vendors' },
    { value: '10K+', label: 'Products Listed' },
    { value: '50K+', label: 'Happy Buyers' },
    { value: '24/7', label: 'Customer Support' },
];

const VALUES = [
    {
        icon: ShieldCheck,
        color: 'text-green-500',
        bg: 'bg-green-50',
        title: 'Buyer Protection',
        desc: '7-day return guarantee on all orders. Shop with full confidence knowing your purchase is protected.',
    },
    {
        icon: Store,
        color: 'text-blue-500',
        bg: 'bg-blue-50',
        title: 'Verified Vendors',
        desc: 'Every shop on PG Market goes through a KYC verification process before they can sell.',
    },
    {
        icon: Truck,
        color: 'text-orange-500',
        bg: 'bg-orange-50',
        title: 'Fast Delivery',
        desc: 'Same-day delivery in Phnom Penh. Nationwide shipping via Grab, J&T, Kerry, and Cambodia Post.',
    },
    {
        icon: Star,
        color: 'text-amber-500',
        bg: 'bg-amber-50',
        title: 'Authentic Reviews',
        desc: 'Only verified buyers can leave reviews — no fake ratings, ever.',
    },
    {
        icon: Package,
        color: 'text-purple-500',
        bg: 'bg-purple-50',
        title: 'Local Brands',
        desc: 'We champion Cambodian-made products and local entrepreneurs across every category.',
    },
    {
        icon: Users,
        color: 'text-rose-500',
        bg: 'bg-rose-50',
        title: 'Community First',
        desc: 'PG Market is built for the Cambodian community — buyers and sellers growing together.',
    },
];

const TEAM = [
    { name: 'Sopheap Chan', role: 'CEO & Founder',      initial: 'S', from: 'from-blue-500',   to: 'to-indigo-600' },
    { name: 'Dara Keo',     role: 'Head of Operations', initial: 'D', from: 'from-emerald-500', to: 'to-teal-600'   },
    { name: 'Sreymom Lim',  role: 'Head of Vendors',    initial: 'S', from: 'from-pink-500',    to: 'to-rose-600'   },
    { name: 'Visal Nget',   role: 'CTO',                initial: 'V', from: 'from-orange-500',  to: 'to-amber-600'  },
];

export default function About() {
    return (
        <StorefrontLayout>
            <Head title="About Us — PG Market" />

            {/* Hero */}
            <div className="relative overflow-hidden bg-linear-to-br from-secondary via-secondary/90 to-primary/80 py-20 text-white">
                <div className="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/5 blur-3xl" />
                <div className="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-white/5 blur-2xl" />
                <div className="relative mx-auto max-w-3xl px-4 text-center">
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-white/20 px-5 py-1.5 text-sm font-semibold backdrop-blur-sm">
                        🇰🇭 Made in Cambodia
                    </div>
                    <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl">
                        Cambodia's #1 Multi-Vendor Marketplace
                    </h1>
                    <p className="mx-auto mt-4 max-w-xl text-lg text-white/80">
                        PG Market connects local Cambodian vendors with buyers across the country — making it easier to discover, trust, and shop from verified local brands.
                    </p>
                    <div className="mt-8 flex flex-wrap justify-center gap-4">
                        <Link href={route('shops.index')} className="rounded-full bg-white px-6 py-3 font-bold text-secondary shadow transition hover:bg-gray-100">
                            Browse Shops <ArrowRight className="ml-1 inline h-4 w-4" />
                        </Link>
                        <Link href={route('contact')} className="rounded-full border border-white/40 px-6 py-3 font-semibold text-white transition hover:bg-white/10">
                            Contact Us
                        </Link>
                    </div>
                </div>
            </div>

            {/* Stats */}
            <section className="bg-white border-b">
                <div className="mx-auto max-w-5xl px-4 py-10">
                    <div className="grid grid-cols-2 gap-6 sm:grid-cols-4 text-center">
                        {STATS.map(({ value, label }) => (
                            <div key={label}>
                                <p className="text-3xl font-extrabold text-primary">{value}</p>
                                <p className="mt-1 text-sm text-gray-500">{label}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Mission */}
            <section className="mx-auto max-w-4xl px-4 py-16 text-center">
                <h2 className="text-3xl font-extrabold text-gray-900">Our Mission</h2>
                <p className="mx-auto mt-4 max-w-2xl text-lg leading-relaxed text-gray-600">
                    We believe every Cambodian entrepreneur deserves a reliable digital storefront. PG Market was built to remove the barriers between local vendors and modern e-commerce — giving every shop the tools to sell, grow, and thrive online.
                </p>
            </section>

            {/* Values */}
            <section className="bg-gray-50 py-16">
                <div className="mx-auto max-w-6xl px-4">
                    <h2 className="mb-10 text-center text-2xl font-extrabold text-gray-900">Why Choose PG Market</h2>
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {VALUES.map(({ icon: Icon, color, bg, title, desc }) => (
                            <div key={title} className="flex gap-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                                <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ${bg}`}>
                                    <Icon className={`h-5 w-5 ${color}`} />
                                </div>
                                <div>
                                    <h3 className="font-bold text-gray-900">{title}</h3>
                                    <p className="mt-1 text-sm leading-relaxed text-gray-500">{desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Team */}
            <section className="mx-auto max-w-5xl px-4 py-16">
                <h2 className="mb-10 text-center text-2xl font-extrabold text-gray-900">Meet the Team</h2>
                <div className="grid grid-cols-2 gap-6 sm:grid-cols-4">
                    {TEAM.map(({ name, role, initial, from, to }) => (
                        <div key={name} className="flex flex-col items-center text-center">
                            <div className={`mb-3 flex h-20 w-20 items-center justify-center rounded-2xl bg-linear-to-br ${from} ${to} text-3xl font-extrabold text-white shadow-md`}>
                                {initial}
                            </div>
                            <p className="font-bold text-gray-900">{name}</p>
                            <p className="mt-0.5 text-xs text-gray-400">{role}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* CTA */}
            <section className="bg-linear-to-br from-secondary to-primary py-16 text-white">
                <div className="mx-auto max-w-2xl px-4 text-center">
                    <h2 className="text-3xl font-extrabold">Ready to Start Selling?</h2>
                    <p className="mt-3 text-white/80">Join thousands of vendors already growing their business on PG Market.</p>
                    <Link href="/register" className="mt-6 inline-block rounded-full bg-white px-8 py-3 font-bold text-secondary shadow-lg transition hover:bg-gray-100">
                        Open Your Shop Today
                    </Link>
                </div>
            </section>
        </StorefrontLayout>
    );
}
