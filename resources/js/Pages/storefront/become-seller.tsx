import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import {
    Store, CheckCircle, DollarSign, BarChart3, Headphones, Shield,
    Rocket, ArrowRight,
} from 'lucide-react';

const BENEFITS = [
    { icon: DollarSign, title: 'Earn Revenue', desc: 'Sell your products to thousands of buyers across Cambodia.' },
    { icon: BarChart3, title: 'Dashboard & Analytics', desc: 'Track orders, revenue, and customer insights in real-time.' },
    { icon: Shield, title: 'Secure Payments', desc: 'Get paid via ABA PayWay with 7-day escrow protection.' },
    { icon: Headphones, title: 'Seller Support', desc: 'Dedicated support team to help you succeed.' },
    { icon: Rocket, title: 'Easy Setup', desc: 'Create your shop in minutes. No technical skills required.' },
    { icon: Store, title: 'Your Own Store', desc: 'Customizable shop page with logo, banner, and product catalog.' },
];

const TERMS = `SELLER AGREEMENT — PG Market

Effective Date: Upon acceptance

This Seller Agreement ("Agreement") is entered into between you ("Seller") and PG Market ("Platform"), operated by Corasoft Solutions.

1. ELIGIBILITY
You must be at least 18 years old and have a valid business or individual seller status in Cambodia. By accepting this Agreement, you confirm that all information provided is accurate.

2. SELLER OBLIGATIONS
• You agree to list only genuine, legal products.
• Product descriptions, images, and prices must be accurate and not misleading.
• You must fulfill orders within the timeframe specified (typically 2-3 business days).
• You are responsible for product quality and customer satisfaction.

3. COMMISSION & FEES
• The Platform charges a commission of 8% on each completed sale (may vary per shop, set by admin).
• Commission is automatically deducted from your earnings before payout.
• There are no listing fees or monthly subscription fees.

4. PAYMENT & PAYOUTS
• Customer payments are collected by the Platform via ABA PayWay.
• Funds are held in escrow for 7 days after delivery confirmation.
• After the escrow period, earnings (minus commission) become available for withdrawal.
• Payouts are processed upon your request and approved by the Platform admin.

5. ORDER FULFILLMENT
• You must accept or reject orders within 24 hours.
• You are responsible for packing and preparing orders for delivery.
• The Platform may integrate delivery services; shipping arrangements will be communicated separately.

6. RETURNS & REFUNDS
• Buyers may request returns within 7 days of delivery.
• You must cooperate with the Platform's dispute resolution process.
• Refunds are processed from your wallet balance.

7. PROHIBITED ITEMS
You may NOT sell: counterfeit goods, illegal substances, weapons, stolen property, or any items prohibited by Cambodian law.

8. ACCOUNT SUSPENSION
The Platform reserves the right to suspend or terminate your seller account for:
• Repeated order cancellations or late shipments.
• Selling prohibited items.
• Fraudulent activity or misrepresentation.
• Violation of any terms in this Agreement.

9. INTELLECTUAL PROPERTY
You retain ownership of your product content. By listing on PG Market, you grant the Platform a non-exclusive license to display your products for marketing purposes.

10. LIMITATION OF LIABILITY
The Platform is not liable for disputes between buyers and sellers beyond providing the dispute resolution mechanism. Maximum liability is limited to the commission earned on the disputed transaction.

11. MODIFICATIONS
The Platform may update this Agreement with 30 days' notice. Continued use of the Platform after notification constitutes acceptance.

By checking the box below, you acknowledge that you have read, understood, and agree to be bound by this Seller Agreement.`;

export default function BecomeSeller() {
    const [accepted, setAccepted] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        shop_name: '',
        accept_terms: false,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('become-seller.store'));
    }

    return (
        <StorefrontLayout>
            <Head title="Become a Seller" />

            {/* Hero */}
            <div className="bg-linear-to-br from-primary via-orange-500 to-amber-500">
                <div className="mx-auto max-w-4xl px-4 py-16 text-center text-white">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm">
                        <Store className="h-8 w-8" />
                    </div>
                    <h1 className="text-3xl font-extrabold tracking-tight sm:text-4xl">
                        Become a Seller on PG Market
                    </h1>
                    <p className="mx-auto mt-3 max-w-lg text-lg text-white/80">
                        Start your online business today. Reach thousands of buyers across Cambodia with zero upfront costs.
                    </p>
                </div>
            </div>

            <div className="mx-auto max-w-4xl px-4 py-12">

                {/* Benefits Grid */}
                <div className="mb-12">
                    <h2 className="mb-6 text-center text-xl font-bold text-gray-900">Why Sell on PG Market?</h2>
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
                        {BENEFITS.map(({ icon: Icon, title, desc }) => (
                            <div key={title} className="rounded-2xl border bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
                                <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <Icon className="h-5 w-5" />
                                </div>
                                <h3 className="text-sm font-bold text-gray-900">{title}</h3>
                                <p className="mt-1 text-xs text-gray-500">{desc}</p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Terms & Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Terms Card */}
                    <div className="rounded-2xl border bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h2 className="text-lg font-bold text-gray-900">Seller Agreement</h2>
                            <p className="mt-1 text-sm text-gray-500">Please read the full agreement before proceeding.</p>
                        </div>
                        <div className="p-6">
                            <div className="h-80 overflow-y-auto rounded-xl border bg-gray-50 p-5 text-sm leading-relaxed text-gray-600 whitespace-pre-line">
                                {TERMS}
                            </div>
                        </div>
                        <div className="border-t px-6 py-4">
                            <label className="flex items-start gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.accept_terms}
                                    onChange={(e) => {
                                        setData('accept_terms', e.target.checked);
                                        setAccepted(e.target.checked);
                                    }}
                                    className="mt-0.5 rounded border-gray-300 text-primary focus:ring-primary"
                                />
                                <span className="text-sm text-gray-700">
                                    I have read and accept the <strong>Seller Agreement</strong>. I understand the commission structure, payout terms, and my obligations as a seller on PG Market.
                                </span>
                            </label>
                            {errors.accept_terms && (
                                <p className="mt-2 text-xs text-red-500">{errors.accept_terms}</p>
                            )}
                        </div>
                    </div>

                    {/* Shop Name */}
                    <div className="rounded-2xl border bg-white p-6 shadow-sm">
                        <h2 className="mb-4 text-lg font-bold text-gray-900">Your Shop</h2>
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700">
                                Shop Name <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.shop_name}
                                onChange={(e) => setData('shop_name', e.target.value)}
                                placeholder="e.g. Phnom Penh Electronics, Siem Reap Crafts..."
                                className="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                            />
                            {errors.shop_name && (
                                <p className="mt-1.5 text-xs text-red-500">{errors.shop_name}</p>
                            )}
                            <p className="mt-2 text-xs text-gray-400">
                                You can change your shop name, add a logo, and customize your shop later from the Vendor Panel.
                            </p>
                        </div>
                    </div>

                    {/* Submit */}
                    <button
                        type="submit"
                        disabled={processing || !accepted || !data.shop_name.trim()}
                        className="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-4 text-base font-bold text-white shadow-lg shadow-primary/25 transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing ? (
                            'Creating your shop…'
                        ) : (
                            <>
                                <CheckCircle className="h-5 w-5" />
                                Create My Shop
                                <ArrowRight className="h-5 w-5" />
                            </>
                        )}
                    </button>

                    {errors.role && (
                        <p className="text-center text-sm text-red-500">{errors.role}</p>
                    )}
                    {errors.shop && (
                        <p className="text-center text-sm text-red-500">{errors.shop}</p>
                    )}
                </form>
            </div>
        </StorefrontLayout>
    );
}
