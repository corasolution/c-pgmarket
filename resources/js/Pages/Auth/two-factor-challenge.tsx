import { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorChallenge() {
    const form = useForm({ code: '' });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        form.post(route('two-factor.verify'));
    }

    return (
        <>
            <Head title="Two-factor authentication" />
            <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
                <div className="max-w-sm w-full bg-white rounded-2xl shadow-lg p-8">
                    <div className="flex justify-center mb-4">
                        <div className="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center">
                            <svg className="w-7 h-7 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>

                    <h1 className="text-xl font-bold text-gray-900 text-center mb-1">Verify your identity</h1>
                    <p className="text-gray-500 text-sm text-center mb-6">
                        Enter the code from your authenticator app, or a recovery code.
                    </p>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <input
                                type="text"
                                inputMode="numeric"
                                maxLength={10}
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value)}
                                className="w-full border border-gray-300 rounded-xl px-4 py-3 text-center text-2xl font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-orange-400"
                                placeholder="000000"
                                autoFocus
                                autoComplete="one-time-code"
                            />
                            {form.errors.code && (
                                <p className="mt-1 text-sm text-red-600 text-center">{form.errors.code}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition disabled:opacity-50"
                        >
                            {form.processing ? 'Verifying…' : 'Verify'}
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}


