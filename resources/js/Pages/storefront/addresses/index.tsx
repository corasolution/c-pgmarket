import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import StorefrontLayout from '@/Layouts/Storefront/StorefrontLayout';
import {
    MapPin, Plus, Pencil, Trash2, Star, X, Home, Building2, MapPinned,
} from 'lucide-react';

interface Address {
    id: number;
    label: string;
    name: string;
    phone: string;
    address_line: string;
    city: string;
    province: string | null;
    is_default: boolean;
}

interface Props {
    addresses: Address[];
}

const LABEL_OPTIONS = ['Home', 'Office', 'Other'];
const LABEL_ICONS: Record<string, React.ElementType> = {
    Home: Home,
    Office: Building2,
    Other: MapPinned,
};

function AddressForm({
    initial,
    onSubmit,
    onCancel,
    submitLabel,
}: {
    initial?: Partial<Address>;
    onSubmit: (data: Record<string, string | boolean>) => void;
    onCancel: () => void;
    submitLabel: string;
}) {
    const { data, setData, processing } = useForm({
        label: initial?.label ?? 'Home',
        name: initial?.name ?? '',
        phone: initial?.phone ?? '',
        address_line: initial?.address_line ?? '',
        city: initial?.city ?? '',
        province: initial?.province ?? '',
        is_default: initial?.is_default ?? false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSubmit(data);
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            {/* Label selector */}
            <div>
                <label className="mb-1.5 block text-sm font-medium text-gray-700">Address Label</label>
                <div className="flex gap-2">
                    {LABEL_OPTIONS.map((l) => (
                        <button
                            key={l}
                            type="button"
                            onClick={() => setData('label', l)}
                            className={`flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors ${
                                data.label === l
                                    ? 'border-primary bg-primary/5 font-semibold text-primary'
                                    : 'border-gray-200 text-gray-600 hover:border-gray-300'
                            }`}
                        >
                            {(() => { const Icon = LABEL_ICONS[l] ?? MapPinned; return <Icon className="h-3.5 w-3.5" />; })()}
                            {l}
                        </button>
                    ))}
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label className="mb-1.5 block text-sm font-medium text-gray-700">Recipient Name *</label>
                    <input
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                        required
                    />
                </div>
                <div>
                    <label className="mb-1.5 block text-sm font-medium text-gray-700">Phone *</label>
                    <input
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                        required
                    />
                </div>
            </div>

            <div>
                <label className="mb-1.5 block text-sm font-medium text-gray-700">Address *</label>
                <input
                    value={data.address_line}
                    onChange={(e) => setData('address_line', e.target.value)}
                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                    placeholder="Street address, house number, village..."
                    required
                />
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label className="mb-1.5 block text-sm font-medium text-gray-700">City *</label>
                    <input
                        value={data.city}
                        onChange={(e) => setData('city', e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                        required
                    />
                </div>
                <div>
                    <label className="mb-1.5 block text-sm font-medium text-gray-700">Province</label>
                    <input
                        value={data.province}
                        onChange={(e) => setData('province', e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                    />
                </div>
            </div>

            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={data.is_default}
                    onChange={(e) => setData('is_default', e.target.checked)}
                    className="rounded border-gray-300 text-primary focus:ring-primary"
                />
                Set as default address
            </label>

            <div className="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-50"
                >
                    {processing ? 'Saving...' : submitLabel}
                </button>
                <button
                    type="button"
                    onClick={onCancel}
                    className="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                >
                    Cancel
                </button>
            </div>
        </form>
    );
}

export default function AddressIndex({ addresses }: Props) {
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const handleAdd = (data: Record<string, string | boolean>) => {
        router.post(route('addresses.store'), data, {
            preserveScroll: true,
            onSuccess: () => setShowForm(false),
        });
    };

    const handleUpdate = (id: number, data: Record<string, string | boolean>) => {
        router.put(route('addresses.update', id), data, {
            preserveScroll: true,
            onSuccess: () => setEditingId(null),
        });
    };

    const handleDelete = (id: number) => {
        if (!confirm('Are you sure you want to remove this address?')) return;
        router.delete(route('addresses.destroy', id), { preserveScroll: true });
    };

    const handleSetDefault = (id: number) => {
        router.patch(route('addresses.set-default', id), {}, { preserveScroll: true });
    };

    return (
        <StorefrontLayout>
            <Head title="My Addresses" />

            <div className="mx-auto max-w-3xl px-4 py-10">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">My Addresses</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Manage your delivery addresses. Your default address will be pre-filled at checkout.
                        </p>
                    </div>
                    {!showForm && (
                        <button
                            onClick={() => { setShowForm(true); setEditingId(null); }}
                            className="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary/90"
                        >
                            <Plus className="h-4 w-4" />
                            Add Address
                        </button>
                    )}
                </div>

                {/* Add form */}
                {showForm && (
                    <div className="mb-6 rounded-2xl border border-primary/20 bg-primary/5 p-6">
                        <h2 className="mb-4 text-lg font-semibold text-gray-900">Add New Address</h2>
                        <AddressForm
                            onSubmit={handleAdd}
                            onCancel={() => setShowForm(false)}
                            submitLabel="Add Address"
                        />
                    </div>
                )}

                {/* Address cards */}
                {addresses.length === 0 && !showForm ? (
                    <div className="rounded-2xl border bg-white p-12 text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <MapPin className="h-7 w-7 text-gray-400" />
                        </div>
                        <p className="font-medium text-gray-700">No addresses yet</p>
                        <p className="mt-1 text-sm text-gray-400">Add your first address for faster checkout.</p>
                        <button
                            onClick={() => setShowForm(true)}
                            className="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary/90"
                        >
                            <Plus className="h-4 w-4" /> Add Address
                        </button>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {addresses.map((addr) =>
                            editingId === addr.id ? (
                                <div key={addr.id} className="rounded-2xl border border-blue-200 bg-blue-50/50 p-6">
                                    <h3 className="mb-4 text-lg font-semibold text-gray-900">Edit Address</h3>
                                    <AddressForm
                                        initial={addr}
                                        onSubmit={(data) => handleUpdate(addr.id, data)}
                                        onCancel={() => setEditingId(null)}
                                        submitLabel="Save Changes"
                                    />
                                </div>
                            ) : (
                                <div
                                    key={addr.id}
                                    className={`group relative rounded-2xl border bg-white p-5 transition-shadow hover:shadow-md ${
                                        addr.is_default ? 'border-primary/30 ring-1 ring-primary/10' : 'border-gray-100'
                                    }`}
                                >
                                    <div className="flex items-start gap-4">
                                        <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                                            addr.is_default ? 'bg-primary/10 text-primary' : 'bg-gray-100 text-gray-500'
                                        }`}>
                                            {(() => { const Icon = LABEL_ICONS[addr.label] ?? MapPinned; return <Icon className="h-5 w-5" />; })()}
                                        </div>

                                        <div className="min-w-0 flex-1">
                                            <div className="mb-1 flex items-center gap-2">
                                                <span className="text-sm font-semibold text-gray-900">{addr.label}</span>
                                                {addr.is_default && (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                                        <Star className="h-3 w-3 fill-current" /> Default
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-sm font-medium text-gray-800">{addr.name}</p>
                                            <p className="text-sm text-gray-500">{addr.phone}</p>
                                            <p className="mt-1 text-sm text-gray-600">
                                                {addr.address_line}, {addr.city}
                                                {addr.province ? `, ${addr.province}` : ''}
                                            </p>
                                        </div>

                                        {/* Actions */}
                                        <div className="flex shrink-0 items-center gap-1">
                                            {!addr.is_default && (
                                                <button
                                                    onClick={() => handleSetDefault(addr.id)}
                                                    title="Set as default"
                                                    className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-primary"
                                                >
                                                    <Star className="h-4 w-4" />
                                                </button>
                                            )}
                                            <button
                                                onClick={() => { setEditingId(addr.id); setShowForm(false); }}
                                                title="Edit"
                                                className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-blue-600"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(addr.id)}
                                                title="Delete"
                                                className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-red-600"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ),
                        )}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
