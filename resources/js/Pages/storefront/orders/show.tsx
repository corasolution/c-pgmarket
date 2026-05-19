import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowLeft, CheckCircle2, Clock, Package, Truck,
    MapPin, ShoppingBag, CreditCard, Box, Navigation, XCircle,
} from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import type { Order } from '@/types';

interface Props { order: Order }

const STEPS = [
    { key: 'pending',    label: 'Order Placed',       icon: Clock },
    { key: 'paid',       label: 'Payment Confirmed',  icon: CreditCard },
    { key: 'accepted',   label: 'Accepted',           icon: CheckCircle2 },
    { key: 'packed',     label: 'Packed',             icon: Box },
    { key: 'picked_up',  label: 'Picked Up',          icon: Truck },
    { key: 'in_transit', label: 'In Transit',         icon: Navigation },
    { key: 'delivered',  label: 'Delivered',          icon: MapPin },
    { key: 'completed',  label: 'Completed',          icon: CheckCircle2 },
];

const STATUS_BADGE: Record<string, string> = {
    pending:    'bg-amber-100 text-amber-700',
    paid:       'bg-blue-100 text-blue-700',
    accepted:   'bg-indigo-100 text-indigo-700',
    packed:     'bg-purple-100 text-purple-700',
    picked_up:  'bg-cyan-100 text-cyan-700',
    in_transit: 'bg-sky-100 text-sky-700',
    delivered:  'bg-green-100 text-green-700',
    completed:  'bg-emerald-100 text-emerald-700',
    cancelled:  'bg-red-100 text-red-700',
};

function StatusTimeline({ status }: { status: string }) {
    const currentIdx = STEPS.findIndex((s) => s.key === status);
    return (
        <div className="overflow-x-auto pb-1">
            <div className="flex min-w-max items-start">
                {STEPS.map((step, i) => {
                    const Icon = step.icon;
                    const done    = i <= currentIdx;
                    const current = i === currentIdx;
                    return (
                        <div key={step.key} className="flex items-start">
                            {/* connector */}
                            {i > 0 && (
                                <div className={`mt-4 h-0.5 w-10 shrink-0 transition-colors ${i <= currentIdx ? 'bg-slate-700' : 'bg-gray-200'}`} />
                            )}
                            <div className="flex w-16 flex-col items-center gap-2">
                                <div className={`flex h-8 w-8 items-center justify-center rounded-full border-2 transition-all ${
                                    current
                                        ? 'border-slate-800 bg-slate-800 text-white shadow-lg shadow-slate-300'
                                        : done
                                        ? 'border-slate-600 bg-slate-600 text-white'
                                        : 'border-gray-200 bg-white text-gray-300'
                                }`}>
                                    <Icon className="h-3.5 w-3.5" />
                                </div>
                                <p className={`text-center text-[10px] leading-tight font-medium ${
                                    current ? 'text-slate-800' : done ? 'text-slate-500' : 'text-gray-300'
                                }`}>
                                    {step.label}
                                </p>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export default function OrderShow({ order }: Props) {
    const statusClass = STATUS_BADGE[order.status ?? 'pending'] ?? 'bg-gray-100 text-gray-600';
    const fmt = (cents: number) => `$${(cents / 100).toFixed(2)}`;
    const [showCancel, setShowCancel] = useState(false);
    const [cancelReason, setCancelReason] = useState('');
    const [cancelling, setCancelling] = useState(false);
    const canCancel = ['pending', 'paid'].includes(order.status ?? '');

    return (
        <StorefrontLayout>
            <Head title={`Order #${order.reference}`} />

            {/* Dark hero header */}
            <div className="bg-gradient-to-br from-slate-800 via-slate-700 to-slate-900">
                <div className="mx-auto max-w-4xl px-4 py-10 sm:px-6">
                    <Link
                        href={route('orders.index')}
                        className="mb-4 inline-flex items-center gap-1.5 text-sm text-slate-400 transition-colors hover:text-white"
                    >
                        <ArrowLeft className="h-4 w-4" /> Back to orders
                    </Link>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p className="text-xs font-medium text-slate-400 uppercase tracking-wider">Order Reference</p>
                            <h1 className="mt-1 font-mono text-2xl font-bold text-white tracking-tight">
                                #{order.reference}
                            </h1>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className={`inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold capitalize ${statusClass}`}>
                                {order.status?.replaceAll('_', ' ')}
                            </span>
                            {canCancel && (
                                <button
                                    onClick={() => setShowCancel(true)}
                                    className="inline-flex items-center gap-1.5 rounded-full bg-red-500/20 px-3 py-1 text-sm font-semibold text-red-300 transition hover:bg-red-500/30"
                                >
                                    <XCircle className="h-3.5 w-3.5" /> Cancel Order
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Cancel confirmation modal */}
            {showCancel && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <h3 className="text-lg font-bold text-gray-900">Cancel Order</h3>
                        <p className="mt-1 text-sm text-gray-500">Are you sure you want to cancel order #{order.reference}?</p>
                        <textarea
                            value={cancelReason}
                            onChange={(e) => setCancelReason(e.target.value)}
                            placeholder="Reason for cancellation (optional)"
                            rows={3}
                            className="mt-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-red-400 focus:ring-1 focus:ring-red-400"
                        />
                        <div className="mt-4 flex justify-end gap-3">
                            <button
                                onClick={() => setShowCancel(false)}
                                className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                            >
                                Keep Order
                            </button>
                            <button
                                disabled={cancelling}
                                onClick={() => {
                                    setCancelling(true);
                                    router.post(route('orders.cancel', order.id), { reason: cancelReason }, {
                                        onFinish: () => setCancelling(false),
                                    });
                                }}
                                className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                            >
                                {cancelling ? 'Cancelling...' : 'Yes, Cancel Order'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <div className="mx-auto max-w-4xl px-4 pb-16 sm:px-6">

                {/* Tracking timeline — overlaps hero */}
                <div className="-mt-4 mb-6 overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-black/5">
                    <div className="flex items-center gap-2 border-b border-gray-100 px-6 py-4">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
                            <Truck className="h-4 w-4 text-slate-600" />
                        </div>
                        <h2 className="font-semibold text-gray-900">Order Tracking</h2>
                    </div>
                    <div className="px-6 py-5">
                        <StatusTimeline status={order.status ?? 'pending'} />
                    </div>
                </div>

                {/* Sub-orders */}
                {order.sub_orders?.map((sub) => (
                    <div key={sub.id} className="mb-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5">
                        <div className="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <div className="flex items-center gap-2">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
                                    <ShoppingBag className="h-4 w-4 text-slate-600" />
                                </div>
                                <h2 className="font-semibold text-gray-900">Items</h2>
                            </div>
                            <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${STATUS_BADGE[sub.status ?? 'pending'] ?? 'bg-gray-100 text-gray-600'}`}>
                                {sub.status?.replaceAll('_', ' ')}
                            </span>
                        </div>

                        <div className="divide-y divide-gray-50 px-6">
                            {sub.items?.map((item) => (
                                <div key={item.id} className="flex items-center gap-4 py-4">
                                    <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-slate-50 ring-1 ring-slate-200 overflow-hidden">
                                        {item.product_image
                                            ? <img src={item.product_image} alt={item.product_name} className="h-full w-full object-cover" />
                                            : <Package className="h-6 w-6 text-slate-300" />
                                        }
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="truncate font-medium text-gray-900">{item.product_name}</p>
                                        <p className="mt-0.5 text-sm text-gray-400">Qty: {item.quantity} × {fmt(item.unit_price_cents)}</p>
                                    </div>
                                    <p className="shrink-0 font-bold text-gray-900">
                                        {fmt(item.unit_price_cents * item.quantity)}
                                    </p>
                                </div>
                            ))}
                        </div>

                        {/* Sub-order subtotal */}
                        <div className="border-t border-gray-100 bg-slate-50 px-6 py-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-500">Subtotal</span>
                                <span className="font-semibold text-gray-800">{fmt(sub.subtotal_cents ?? 0)}</span>
                            </div>
                        </div>
                    </div>
                ))}

                {/* Order summary */}
                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5">
                    <div className="flex items-center gap-2 border-b border-gray-100 px-6 py-4">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100">
                            <CreditCard className="h-4 w-4 text-slate-600" />
                        </div>
                        <h2 className="font-semibold text-gray-900">Order Summary</h2>
                    </div>
                    <div className="divide-y divide-gray-50 px-6 py-2">
                        {order.sub_orders?.map((sub) => (
                            <div key={sub.id} className="flex justify-between py-3 text-sm">
                                <span className="text-gray-500">Items subtotal</span>
                                <span className="font-medium text-gray-800">{fmt(sub.subtotal_cents ?? 0)}</span>
                            </div>
                        ))}
                        <div className="flex justify-between py-3 text-sm">
                            <span className="text-gray-500">Shipping</span>
                            <span className="font-medium text-gray-800">
                                {order.sub_orders?.[0]?.shipping_fee_cents
                                    ? fmt(order.sub_orders[0].shipping_fee_cents)
                                    : 'Free'}
                            </span>
                        </div>
                        <div className="flex justify-between py-4">
                            <span className="text-base font-bold text-gray-900">Total</span>
                            <span className="text-xl font-bold text-slate-800">{fmt(order.total_cents ?? 0)}</span>
                        </div>
                    </div>
                </div>

            </div>
        </StorefrontLayout>
    );
}


