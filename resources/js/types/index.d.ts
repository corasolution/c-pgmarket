import { Config } from 'ziggy-js';

export interface User {
    id: number;
    name: string;
    email: string;
    role: 'buyer' | 'vendor_owner' | 'vendor_staff' | 'admin';
    phone?: string;
    locale: string;
    email_verified_at?: string;
}

export interface Shop {
    id: number;
    name: string;
    slug: string;
    description_i18n?: Record<string, string>;
    logo?: string;
    banner?: string;
    status: 'draft' | 'submitted' | 'active' | 'suspended' | 'rejected';
    commission_percent: number;
    currency: string;
    phone?: string;
    email?: string;
}

export interface Category {
    id: number;
    name_i18n: Record<string, string>;
    slug: string;
    image?: string;
    sort_order: number;
    parent_id?: number | null;
    children?: Category[];
    products_count?: number;
}

export interface Brand {
    id: number;
    name_i18n: Record<string, string>;
    slug: string;
    logo?: string | null;
    sort_order?: number;
    is_active?: boolean;
}

export interface ProductVariant {
    id: number;
    product_id: number;
    sku: string;
    options?: Record<string, string>;
    price_cents: number;
    price_currency: string;
    stock_quantity: number;
    image?: string;
    is_active: boolean;
}

export interface Product {
    id: number;
    shop_id: number;
    category_id?: number;
    brand_id?: number | null;
    name_i18n: Record<string, string>;
    description_i18n?: Record<string, string>;
    slug: string;
    images?: string[];
    status: 'draft' | 'active' | 'archived';
    is_featured: boolean;
    stock_track: boolean;
    shop?: Shop;
    category?: Category;
    brand?: Brand;
    variants?: ProductVariant[];
}

export interface CartItem {
    id: number;
    cart_id: number;
    product_variant_id: number;
    quantity: number;
    unit_price_cents: number;
    unit_price_currency: string;
    variant?: ProductVariant & { product?: Product };
}

export interface Cart {
    id: number;
    items?: CartItem[];
}

export interface OrderItem {
    id: number;
    sub_order_id: number;
    product_name: string;
    product_name_snapshot: string;
    product_image?: string;
    image_snapshot?: string;
    variant_sku_snapshot: string;
    quantity: number;
    unit_price_cents: number;
    unit_price_currency: string;
}

export interface SubOrder {
    id: number;
    order_id: number;
    shop_id: number;
    status: string;
    subtotal_cents: number;
    subtotal_currency: string;
    shipping_fee_cents?: number;
    shop?: Shop;
    items?: OrderItem[];
}

export interface Order {
    id: number;
    reference: string;
    buyer_id: number;
    status: string;
    total_cents: number;
    total_currency: string;
    created_at?: string;
    shipping_address?: Record<string, string>;
    sub_orders?: SubOrder[];
}

export interface MessageAttachment {
    id: number;
    message_id: number;
    filename: string;
    path: string;
    mime_type: string;
    size_bytes: number;
}

export interface Message {
    id: number;
    conversation_id: number;
    sender_id: number;
    body: string;
    created_at: string;
    sender?: User;
    attachments?: MessageAttachment[];
}

export interface Conversation {
    id: number;
    buyer_id: number;
    shop_id: number;
    last_message_at?: string;
    buyer?: User;
    shop?: Shop;
    messages?: Message[];
    latest_message?: Message;
}

export interface WalletTransaction {
    id: number;
    vendor_wallet_id: number;
    sub_order_id?: number;
    type: 'credit' | 'debit';
    reason: 'order_payment' | 'escrow_release' | 'commission' | 'refund' | 'payout' | 'adjustment';
    amount_cents: number;
    amount_currency: string;
    balance_after_cents: number;
    reference?: string;
    note?: string;
    created_at: string;
}

export interface VendorWallet {
    id: number;
    shop_id: number;
    available_balance_cents: number;
    available_balance_currency: string;
    pending_balance_cents: number;
    pending_balance_currency: string;
    lifetime_earned_cents: number;
    transactions?: WalletTransaction[];
}

export interface Payout {
    id: number;
    shop_id: number;
    amount_cents: number;
    amount_currency: string;
    status: 'pending' | 'approved' | 'rejected';
    bank_name: string;
    bank_account_number: string;
    bank_account_name: string;
    rejection_reason?: string;
    approved_by?: number;
    approved_at?: string;
    created_at: string;
    shop?: Shop;
}

export interface DisputeMessage {
    id: number;
    dispute_id: number;
    sender_id: number;
    body: string;
    created_at: string;
    sender?: User;
}

export interface Dispute {
    id: number;
    order_item_id: number;
    buyer_id: number;
    shop_id: number;
    reason: string;
    description?: string;
    status: 'open' | 'in_review' | 'resolved' | 'closed';
    resolution?: string;
    resolved_at?: string;
    created_at: string;
    order_item?: OrderItem;
    messages?: DisputeMessage[];
}

export interface Review {
    id: number;
    order_item_id: number;
    buyer_id: number;
    product_variant_id: number;
    rating: number;
    body?: string;
    created_at: string;
    buyer?: User;
}

export interface Shipment {
    id: number;
    sub_order_id: number;
    provider: string;
    tracking_number: string;
    status: 'pending' | 'picked_up' | 'in_transit' | 'delivered' | 'failed' | 'cancelled' | 'returned';
    shipping_fee_cents: number;
    shipping_fee_currency: string;
    estimated_delivery_at?: string;
    delivered_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    ziggy: Config & { location: string };
    locale: string;
    flash?: {
        success?: string;
        error?: string;
    };
    navCategories: Category[];
    cartCount: number;
    chatbotEnabled: boolean;
    favoriteIds: number[];
    siteLogo: string;
};
