import { router, usePage } from '@inertiajs/react';
import { Heart } from 'lucide-react';
import { useState } from 'react';
import type { PageProps } from '@/types';

interface Props {
    productId: number;
    size?: 'sm' | 'md' | 'lg';
}

export default function HeartButton({ productId, size = 'sm' }: Props) {
    const { favoriteIds } = usePage<PageProps>().props;
    const [optimistic, setOptimistic] = useState<boolean | null>(null);

    const isFav = optimistic ?? favoriteIds?.includes(productId) ?? false;

    function handleClick(e: React.MouseEvent) {
        e.preventDefault();
        e.stopPropagation();
        setOptimistic(!isFav);
        router.post(
            route('favorites.toggle', productId),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => setOptimistic(null),
                onFinish: () => setOptimistic(null),
            },
        );
    }

    const dim = size === 'lg' ? 'h-12 w-12' : size === 'md' ? 'h-8 w-8' : 'h-6 w-6';
    const icon = size === 'lg' ? 'h-6 w-6' : size === 'md' ? 'h-4 w-4' : 'h-3.5 w-3.5';

    return (
        <button
            onClick={handleClick}
            title={isFav ? 'Remove from favorites' : 'Add to favorites'}
            className={`${dim} flex items-center justify-center rounded-full transition-all duration-200 ${
                isFav
                    ? 'bg-red-500 text-white shadow-sm shadow-red-200'
                    : 'bg-white/90 text-gray-400 shadow-sm hover:bg-red-50 hover:text-red-400'
            }`}
        >
            <Heart className={`${icon} ${isFav ? 'fill-current' : ''}`} />
        </button>
    );
}
