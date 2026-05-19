import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Mail, Phone, MapPin, Clock, MessageSquare, Send, CheckCircle } from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';

const CONTACT_INFO = [
    {
        icon: MapPin,
        color: 'text-blue-500',
        bg: 'bg-blue-50',
        label: 'Address',
        value: 'Phnom Penh, Cambodia\nKhan Daun Penh District',
    },
    {
        icon: Phone,
        color: 'text-green-500',
        bg: 'bg-green-50',
        label: 'Phone',
        value: '+855 70 85 4444',
    },
    {
        icon: Mail,
        color: 'text-orange-500',
        bg: 'bg-orange-50',
        label: 'Email',
        value: 'contact@pgmarket.online',
    },
    {
        icon: Clock,
        color: 'text-purple-500',
        bg: 'bg-purple-50',
        label: 'Support Hours',
        value: 'Mon – Sat: 8:00 AM – 6:00 PM\nSunday: 9:00 AM – 4:00 PM',
    },
];

const TOPICS = ['General Inquiry', 'Order Issue', 'Vendor Support', 'Payment Problem', 'Report a Seller', 'Other'];

export default function Contact() {
    const [sent, setSent] = useState(false);
    const [form, setForm] = useState({ name: '', email: '', topic: '', message: '' });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setSent(true);
    }

    return (
        <StorefrontLayout>
            <Head title="Contact Us — PG Market" />

            {/* Hero */}
            <div className="relative overflow-hidden bg-linear-to-br from-secondary via-secondary/90 to-primary/80 py-16 text-white">
                <div className="absolute -right-20 -top-20 h-80 w-80 rounded-full bg-white/5 blur-3xl" />
                <div className="relative mx-auto max-w-2xl px-4 text-center">
                    <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-white/20 px-5 py-1.5 text-sm font-semibold backdrop-blur-sm">
                        <MessageSquare className="h-4 w-4" />
                        We&apos;re here to help
                    </div>
                    <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl">Contact Us</h1>
                    <p className="mt-3 text-lg text-white/80">
                        Have a question or issue? Our support team responds within 24 hours.
                    </p>
                </div>
            </div>

            <div className="mx-auto max-w-6xl px-4 py-14">
                <div className="grid grid-cols-1 gap-10 lg:grid-cols-3">

                    {/* Contact info */}
                    <div className="space-y-4">
                        <h2 className="text-xl font-extrabold text-gray-900">Get in Touch</h2>
                        <p className="text-sm leading-relaxed text-gray-500">
                            Whether you&apos;re a buyer or a vendor, we&apos;re happy to help. Reach out through any of the channels below.
                        </p>

                        <div className="space-y-3 pt-2">
                            {CONTACT_INFO.map(({ icon: Icon, color, bg, label, value }) => (
                                <div key={label} className="flex gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                                    <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${bg}`}>
                                        <Icon className={`h-5 w-5 ${color}`} />
                                    </div>
                                    <div>
                                        <p className="text-xs font-semibold text-gray-400 uppercase tracking-wide">{label}</p>
                                        <p className="mt-0.5 text-sm font-medium text-gray-800 whitespace-pre-line">{value}</p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Social links */}
                        <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                            <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Social Media</p>
                            <div className="flex gap-3">
                                {[
                                    { name: 'FB',  bg: 'bg-[#1877F2]', href: '#' },
                                    { name: 'TG',  bg: 'bg-[#26A5E4]', href: '#' },
                                    { name: 'WA',  bg: 'bg-[#25D366]', href: '#' },
                                ].map(({ name, bg, href }) => (
                                    <a key={name} href={href}
                                        className={`flex h-9 w-9 items-center justify-center rounded-xl ${bg} text-xs font-bold text-white shadow-sm transition hover:opacity-90`}>
                                        {name}
                                    </a>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Contact form */}
                    <div className="lg:col-span-2">
                        <div className="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                            {sent ? (
                                <div className="flex flex-col items-center justify-center py-16 text-center">
                                    <CheckCircle className="mb-4 h-16 w-16 text-green-500" />
                                    <h3 className="text-xl font-extrabold text-gray-900">Message Sent!</h3>
                                    <p className="mt-2 text-sm text-gray-500">
                                        Thank you for reaching out. Our team will get back to you within 24 hours.
                                    </p>
                                    <button
                                        onClick={() => { setSent(false); setForm({ name: '', email: '', topic: '', message: '' }); }}
                                        className="mt-6 rounded-full bg-primary px-6 py-2.5 text-sm font-bold text-white transition hover:bg-primary/90"
                                    >
                                        Send Another Message
                                    </button>
                                </div>
                            ) : (
                                <form onSubmit={handleSubmit} className="space-y-5">
                                    <h2 className="text-xl font-extrabold text-gray-900">Send us a Message</h2>

                                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1.5 block text-sm font-semibold text-gray-700">Full Name</label>
                                            <input
                                                required
                                                value={form.name}
                                                onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                                                placeholder="Your full name"
                                                className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1.5 block text-sm font-semibold text-gray-700">Email Address</label>
                                            <input
                                                required
                                                type="email"
                                                value={form.email}
                                                onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
                                                placeholder="your@email.com"
                                                className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-sm font-semibold text-gray-700">Topic</label>
                                        <select
                                            required
                                            value={form.topic}
                                            onChange={e => setForm(f => ({ ...f, topic: e.target.value }))}
                                            className="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 bg-white"
                                        >
                                            <option value="">Select a topic…</option>
                                            {TOPICS.map(t => <option key={t} value={t}>{t}</option>)}
                                        </select>
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-sm font-semibold text-gray-700">Message</label>
                                        <textarea
                                            required
                                            rows={5}
                                            value={form.message}
                                            onChange={e => setForm(f => ({ ...f, message: e.target.value }))}
                                            placeholder="Describe your issue or question in detail…"
                                            className="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        className="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary py-3.5 text-base font-bold text-white shadow-lg shadow-primary/25 transition hover:bg-primary/90 active:scale-[0.98]"
                                    >
                                        <Send className="h-4 w-4" />
                                        Send Message
                                    </button>

                                    <p className="text-center text-xs text-gray-400">
                                        Or email us directly at{' '}
                                        <a href="mailto:contact@pgmarket.online" className="font-semibold text-primary hover:underline">
                                            contact@pgmarket.online
                                        </a>
                                    </p>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}
