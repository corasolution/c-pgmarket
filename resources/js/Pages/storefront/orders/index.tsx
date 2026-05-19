import { Head, Link } from '@inertiajs/react';
import { Package } from 'lucide-react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import type { Order } from '@/types';

interface Props { orders: Order[] }

const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-700',
    paid: 'bg-blue-100 text-blue-700',
    accepted: 'bg-indigo-100 text-indigo-700',
    packed: 'bg-purple-100 text-purple-700',
    picked_up: 'bg-yellow-100 text-yellow-700',
    in_transit: 'bg-orange-100 text-orange-700',
    delivered: 'bg-teal-100 text-teal-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    refunded: 'bg-pink-100 text-pink-700',
    disputed: 'bg-rose-100 text-rose-700',
};

export default function OrdersIndex({ orders }: Props) {
    return (
        <StorefrontLayout>
            <Head title="My Orders" />
            <div className="mx-auto max-w-4xl px-4 py-10">
                <h1 className="mb-6 text-2xl font-bold text-gray-900">My Orders</h1>

                {orders.length === 0 ? (
                    <div className="flex flex-col items-center gap-4 rounded-2xl border bg-white py-20 text-center">
                        <Package className="h-16 w-16 text-gray-300" />
                        <p className="text-gray-500">You haven't placed any orders yet.</p>
                        <Link href={route('home')} className="rounded-full bg-primary px-6 py-2 text-sm font-semibold text-white hover:bg-primary-dark">
                            Start Shopping
                        </Link>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {orders.map((order) => (
                            <Link
                                key={order.id}
                                href={route('orders.show', order.id)}
                                className="block overflow-hidden rounded-xl border bg-white shadow-sm transition hover:shadow-md"
                            >
                                <div className="flex items-center justify-between border-b bg-gray-50 px-4 py-3">
                                    <div>
                                        <span className="text-xs text-gray-500">Order</span>
                                        <span className="ml-1 font-mono text-sm font-semibold text-gray-900">
                                            #{order.reference}
                                        </span>
                                    </div>
                                    <span className={`rounded-full px-3 py-0.5 text-xs font-semibold capitalize ${STATUS_COLORS[order.status] ?? 'bg-gray-100 text-gray-700'}`}>
                                        {order.status.replaceAll('_', ' ')}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between px-4 py-4">
                                    <p className="text-sm text-gray-600">
                                        {order.sub_orders?.length ?? 0} shop(s) · {order.created_at ? new Date(order.created_at).toLocaleDateString() : ''}
                                    </p>
                                    <p className="font-bold text-primary">
                                        ${((order.total_cents ?? 0) / 100).toFixed(2)}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}


