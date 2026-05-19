export interface CategoryMeta {
    emoji: string;
    from: string;
    to: string;
    glow: string;
}

export const CATEGORY_META: Record<string, CategoryMeta> = {
    // Main categories
    'electronics':              { emoji: '📱', from: 'from-blue-500',    to: 'to-indigo-600',   glow: 'hover:shadow-blue-300/60' },
    'electronic':               { emoji: '📱', from: 'from-blue-500',    to: 'to-indigo-600',   glow: 'hover:shadow-blue-300/60' },
    'fashion':                  { emoji: '👗', from: 'from-pink-400',    to: 'to-rose-600',     glow: 'hover:shadow-pink-300/60' },
    'home-living':              { emoji: '🏠', from: 'from-amber-400',   to: 'to-orange-500',   glow: 'hover:shadow-amber-300/60' },
    'beauty':                   { emoji: '💄', from: 'from-purple-500',  to: 'to-fuchsia-600',  glow: 'hover:shadow-purple-300/60' },
    'beauty-health':            { emoji: '💅', from: 'from-pink-500',    to: 'to-purple-600',   glow: 'hover:shadow-pink-300/60' },
    'sports':                   { emoji: '⚽', from: 'from-green-400',   to: 'to-emerald-600',  glow: 'hover:shadow-green-300/60' },
    'sports-outdoors':          { emoji: '🏕️', from: 'from-green-500',  to: 'to-teal-600',     glow: 'hover:shadow-green-300/60' },
    'food':                     { emoji: '🍜', from: 'from-orange-400',  to: 'to-red-500',      glow: 'hover:shadow-orange-300/60' },
    'food-grocery':             { emoji: '🛒', from: 'from-lime-500',    to: 'to-green-600',    glow: 'hover:shadow-lime-300/60' },
    'beverages':                { emoji: '☕', from: 'from-amber-500',   to: 'to-orange-600',   glow: 'hover:shadow-amber-300/60' },
    'beverage':                 { emoji: '🧋', from: 'from-cyan-400',    to: 'to-blue-600',     glow: 'hover:shadow-cyan-300/60' },
    'automotive-spare-parts':   { emoji: '🚗', from: 'from-red-500',     to: 'to-rose-700',     glow: 'hover:shadow-red-300/60' },
    'car-accessories-spare':    { emoji: '⚙️', from: 'from-zinc-400',    to: 'to-slate-700',    glow: 'hover:shadow-zinc-300/60' },
    'baby-kids':                { emoji: '👶', from: 'from-yellow-300',  to: 'to-amber-500',    glow: 'hover:shadow-yellow-300/60' },
    'baby-kid':                 { emoji: '🧸', from: 'from-yellow-300',  to: 'to-amber-500',    glow: 'hover:shadow-yellow-300/60' },
    'arts-crafts':              { emoji: '🎨', from: 'from-violet-400',  to: 'to-purple-700',   glow: 'hover:shadow-violet-300/60' },
    'arts':                     { emoji: '🎨', from: 'from-violet-400',  to: 'to-purple-700',   glow: 'hover:shadow-violet-300/60' },
    'building-materials':       { emoji: '🏗️', from: 'from-slate-500',  to: 'to-gray-700',     glow: 'hover:shadow-slate-300/60' },
    'building-material':        { emoji: '🏗️', from: 'from-slate-500',  to: 'to-gray-700',     glow: 'hover:shadow-slate-300/60' },
    'tools-hardware':           { emoji: '🔧', from: 'from-gray-500',    to: 'to-zinc-700',     glow: 'hover:shadow-gray-300/60' },
    'tools-main':               { emoji: '🛠️', from: 'from-gray-500',   to: 'to-zinc-800',     glow: 'hover:shadow-gray-300/60' },
    'tools':                    { emoji: '🔧', from: 'from-gray-500',    to: 'to-zinc-800',     glow: 'hover:shadow-gray-300/60' },
    'home-supplies':            { emoji: '🏡', from: 'from-teal-400',    to: 'to-cyan-600',     glow: 'hover:shadow-teal-300/60' },
    'home-supply':              { emoji: '🏡', from: 'from-teal-400',    to: 'to-cyan-700',     glow: 'hover:shadow-teal-300/60' },
    'home-suply':               { emoji: '🏡', from: 'from-teal-400',    to: 'to-cyan-700',     glow: 'hover:shadow-teal-300/60' },
    'pet-supplies':             { emoji: '🐾', from: 'from-amber-400',   to: 'to-orange-600',   glow: 'hover:shadow-amber-300/60' },
    'agriculture-plants':       { emoji: '🌱', from: 'from-emerald-400', to: 'to-green-700',    glow: 'hover:shadow-emerald-300/60' },
    'agri-plants-pet':          { emoji: '🌿', from: 'from-emerald-400', to: 'to-green-700',    glow: 'hover:shadow-emerald-300/60' },
    'musical-instruments':      { emoji: '🎸', from: 'from-purple-500',  to: 'to-indigo-700',   glow: 'hover:shadow-purple-300/60' },
    'musical-instrument':       { emoji: '🎸', from: 'from-purple-500',  to: 'to-indigo-700',   glow: 'hover:shadow-purple-300/60' },
    'books-stationery':         { emoji: '📚', from: 'from-sky-400',     to: 'to-blue-600',     glow: 'hover:shadow-sky-300/60' },
    'services':                 { emoji: '🚚', from: 'from-indigo-400',  to: 'to-blue-700',     glow: 'hover:shadow-indigo-300/60' },

    // Sub/legacy categories
    'fruit':                    { emoji: '🍎', from: 'from-green-400',   to: 'to-emerald-600',  glow: 'hover:shadow-green-300/60' },
    'general':                  { emoji: '📦', from: 'from-gray-400',    to: 'to-slate-600',    glow: 'hover:shadow-gray-300/60' },
    'meatball':                 { emoji: '🍖', from: 'from-red-400',     to: 'to-rose-600',     glow: 'hover:shadow-red-300/60' },
    'phone-accessories':        { emoji: '📲', from: 'from-blue-400',    to: 'to-cyan-600',     glow: 'hover:shadow-blue-300/60' },
    'computer-accessories':     { emoji: '💻', from: 'from-slate-500',   to: 'to-gray-700',     glow: 'hover:shadow-slate-300/60' },
    'kampot-pepper':            { emoji: '🌶️', from: 'from-red-500',    to: 'to-orange-700',   glow: 'hover:shadow-red-300/60' },
    'garlic':                   { emoji: '🧄', from: 'from-lime-400',    to: 'to-green-600',    glow: 'hover:shadow-lime-300/60' },
    'spare-part':               { emoji: '⚙️', from: 'from-zinc-400',    to: 'to-slate-700',    glow: 'hover:shadow-zinc-300/60' },
    'jewelry':                  { emoji: '💍', from: 'from-yellow-400',  to: 'to-amber-600',    glow: 'hover:shadow-yellow-300/60' },
    'toys':                     { emoji: '🎮', from: 'from-red-400',     to: 'to-rose-700',     glow: 'hover:shadow-red-300/60' },
    'clothes-shoes':            { emoji: '👟', from: 'from-rose-400',    to: 'to-pink-700',     glow: 'hover:shadow-rose-300/60' },
    'clothes-shose':            { emoji: '👟', from: 'from-rose-400',    to: 'to-pink-700',     glow: 'hover:shadow-rose-300/60' },
    'furnitures':               { emoji: '🪑', from: 'from-amber-500',   to: 'to-yellow-700',   glow: 'hover:shadow-amber-300/60' },
    'furniture':                { emoji: '🪑', from: 'from-amber-500',   to: 'to-yellow-700',   glow: 'hover:shadow-amber-300/60' },
    'hotel-supply':             { emoji: '🏨', from: 'from-sky-400',     to: 'to-blue-700',     glow: 'hover:shadow-sky-300/60' },
    'hotel-suply':              { emoji: '🏨', from: 'from-sky-400',     to: 'to-blue-700',     glow: 'hover:shadow-sky-300/60' },
    "library's-equipment":      { emoji: '📚', from: 'from-indigo-400',  to: 'to-blue-700',     glow: 'hover:shadow-indigo-300/60' },
    'library-equipment':        { emoji: '📚', from: 'from-indigo-400',  to: 'to-blue-700',     glow: 'hover:shadow-indigo-300/60' },
    'librarys-equipment':       { emoji: '📚', from: 'from-indigo-400',  to: 'to-blue-700',     glow: 'hover:shadow-indigo-300/60' },
    'wood-crafts':              { emoji: '🪵', from: 'from-orange-600',  to: 'to-amber-900',    glow: 'hover:shadow-orange-300/60' },
    'health-wellness':          { emoji: '🌱', from: 'from-green-500',   to: 'to-teal-700',     glow: 'hover:shadow-green-300/60' },
    'automotive':               { emoji: '🚗', from: 'from-gray-600',    to: 'to-slate-800',    glow: 'hover:shadow-gray-300/60' },
    'stationery':               { emoji: '✏️', from: 'from-sky-400',     to: 'to-cyan-600',     glow: 'hover:shadow-sky-300/60' },
    'mobile':                   { emoji: '📲', from: 'from-blue-400',    to: 'to-cyan-600',     glow: 'hover:shadow-blue-300/60' },
    'mobile-accessories':       { emoji: '🎧', from: 'from-indigo-400',  to: 'to-blue-600',     glow: 'hover:shadow-indigo-300/60' },
    'computers':                { emoji: '💻', from: 'from-slate-500',   to: 'to-gray-700',     glow: 'hover:shadow-slate-300/60' },
    'cameras':                  { emoji: '📷', from: 'from-gray-600',    to: 'to-zinc-800',     glow: 'hover:shadow-gray-300/60' },
    'gaming':                   { emoji: '🕹️', from: 'from-purple-500',  to: 'to-indigo-700',   glow: 'hover:shadow-purple-300/60' },
    'books':                    { emoji: '📖', from: 'from-amber-500',   to: 'to-yellow-700',   glow: 'hover:shadow-amber-300/60' },
    'kitchen':                  { emoji: '🍳', from: 'from-orange-400',  to: 'to-red-600',      glow: 'hover:shadow-orange-300/60' },
    'outdoor':                  { emoji: '⛺', from: 'from-green-500',   to: 'to-emerald-700',  glow: 'hover:shadow-green-300/60' },
    'sport-hobby':              { emoji: '🎯', from: 'from-teal-500',    to: 'to-green-600',    glow: 'hover:shadow-teal-300/60' },

    // Earphone & specific product categories
    'earphone':                 { emoji: '🎧', from: 'from-indigo-400',  to: 'to-blue-600',     glow: 'hover:shadow-indigo-300/60' },
    'computer-accessories-general': { emoji: '🖥️', from: 'from-slate-400', to: 'to-gray-600',  glow: 'hover:shadow-slate-300/60' },
    'car-accessories-general':  { emoji: '🚙', from: 'from-zinc-400',    to: 'to-slate-600',    glow: 'hover:shadow-zinc-300/60' },
    'tape-measure':             { emoji: '📏', from: 'from-gray-400',    to: 'to-zinc-600',     glow: 'hover:shadow-gray-300/60' },
    'red-pepper':               { emoji: '🌶️', from: 'from-red-400',    to: 'to-rose-600',     glow: 'hover:shadow-red-300/60' },
    'white-garlic':             { emoji: '🧄', from: 'from-lime-300',    to: 'to-green-500',    glow: 'hover:shadow-lime-300/60' },
    'beef-meatball':            { emoji: '🥩', from: 'from-red-500',     to: 'to-rose-700',     glow: 'hover:shadow-red-300/60' },
    'mango':                    { emoji: '🥭', from: 'from-yellow-400',  to: 'to-amber-600',    glow: 'hover:shadow-yellow-300/60' },
    'rambutan-fruit':           { emoji: '🍇', from: 'from-red-400',     to: 'to-pink-600',     glow: 'hover:shadow-red-300/60' },
    'star-fruit':               { emoji: '⭐', from: 'from-yellow-300',  to: 'to-amber-500',    glow: 'hover:shadow-yellow-300/60' },
    'water-melon':              { emoji: '🍉', from: 'from-green-400',   to: 'to-red-500',      glow: 'hover:shadow-green-300/60' },
};

export const DEFAULT_META: CategoryMeta = {
    emoji: '🛍️',
    from: 'from-secondary/80',
    to: 'to-primary/80',
    glow: 'hover:shadow-gray-300/60',
};

export function getCategoryMeta(slug: string): CategoryMeta {
    return CATEGORY_META[slug] ?? DEFAULT_META;
}
