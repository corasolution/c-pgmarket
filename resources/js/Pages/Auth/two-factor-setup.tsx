import { FormEvent, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { QRCodeSVG } from 'qrcode.react';

interface Props {
    hasSecret: boolean;
    qrUrl?: string;
    secret?: string;
}

export default function TwoFactorSetup({ hasSecret, qrUrl, secret }: Props) {
    const [otpauthUrl, setOtpauthUrl] = useState<string | null>(qrUrl ?? null);
    const [manualSecret, setManualSecret] = useState(secret ?? null);
    const [enabling, setEnabling] = useState(false);

    const confirmForm = useForm({ code: '' });

    function handleEnable() {
        setEnabling(true);
        router.post(
            route('two-factor.enable'),
            {},
            {
                onSuccess: (page) => {
                    const props = page.props as unknown as Props;
                    if (props.qrUrl) {
                        setOtpauthUrl(props.qrUrl);
                        setManualSecret(props.secret ?? null);
                    }
                    setEnabling(false);
                },
                onError: () => setEnabling(false),
            },
        );
    }

    function handleConfirm(e: FormEvent) {
        e.preventDefault();
        confirmForm.post(route('two-factor.confirm'));
    }

    return (
        <>
            <Head title="Set up two-factor authentication" />
            <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
                <div className="max-w-md w-full bg-white rounded-2xl shadow-lg p-8">
                    <h1 className="text-2xl font-bold text-gray-900 mb-2">Two-Factor Authentication</h1>
                    <p className="text-gray-500 mb-6 text-sm">
                        Required for admin and vendor accounts. Scan the QR code with your authenticator app (Google
                        Authenticator, Authy, etc.).
                    </p>

                    {!otpauthUrl ? (
                        <button
                            onClick={handleEnable}
                            disabled={enabling}
                            className="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition disabled:opacity-50"
                        >
                            {enabling ? 'Generating QR Code…' : 'Generate QR Code'}
                        </button>
                    ) : (
                        <div className="space-y-6">
                            <div className="flex justify-center">
                                <QRCodeSVG value={otpauthUrl} size={200} className="rounded-lg border border-gray-200 p-2" />
                            </div>

                            {manualSecret && (
                                <div className="bg-gray-50 rounded-lg p-3 text-center">
                                    <p className="text-xs text-gray-500 mb-1">Manual entry key</p>
                                    <code className="text-sm font-mono tracking-widest text-gray-800">
                                        {manualSecret}
                                    </code>
                                </div>
                            )}

                            <form onSubmit={handleConfirm} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Enter the 6-digit code from your app
                                    </label>
                                    <input
                                        type="text"
                                        inputMode="numeric"
                                        maxLength={6}
                                        value={confirmForm.data.code}
                                        onChange={(e) => confirmForm.setData('code', e.target.value)}
                                        className="w-full border border-gray-300 rounded-xl px-4 py-3 text-center text-2xl font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-orange-400"
                                        placeholder="000000"
                                        autoFocus
                                    />
                                    {confirmForm.errors.code && (
                                        <p className="mt-1 text-sm text-red-600">{confirmForm.errors.code}</p>
                                    )}
                                </div>
                                <button
                                    type="submit"
                                    disabled={confirmForm.processing}
                                    className="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition disabled:opacity-50"
                                >
                                    {confirmForm.processing ? 'Verifying…' : 'Confirm & Enable'}
                                </button>
                            </form>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}


