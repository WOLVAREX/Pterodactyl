import React, { useEffect, useMemo, useState } from 'react';
import { useHistory } from 'react-router-dom';
import Spinner from '@/components/elements/Spinner';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import http from '@/api/http';
import tw from 'twin.macro';
import { useFlashKey } from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';

interface PriceEntry {
    price_kes: number;
    unit_label: string;
}

interface Options {
    eggs: Record<string, { id: number; name: string }[]>;
    prices: Partial<Record<'ram' | 'disk' | 'cpu' | 'database' | 'backup' | 'allocation', PriceEntry>>;
    price_cap: number;
}

interface Config {
    eggId: number | null;
    memory: number;
    disk: number;
    cpu: number;
    databases: number;
    backups: number;
    allocations: number;
}

const DEFAULT_CONFIG: Config = {
    eggId: null,
    memory: 512,
    disk: 1024,
    cpu: 50,
    databases: 0,
    backups: 0,
    allocations: 1,
};

export default () => {
    const history = useHistory();
    const [options, setOptions] = useState<Options | null>(null);
    const [config, setConfig] = useState<Config>(DEFAULT_CONFIG);
    const [purchasing, setPurchasing] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [errorDialog, setErrorDialog] = useState<{ message: string; showTopUp: boolean } | null>(null);
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('account:custom-build');

    useEffect(() => {
        clearFlashes();
        http.get('/account/store/custom/options')
            .then((response) => {
                setOptions(response.data);
                const firstNest = Object.values(response.data.eggs)[0] as { id: number; name: string }[] | undefined;
                if (firstNest && firstNest.length > 0) {
                    setConfig((c) => ({ ...c, eggId: firstNest[0].id }));
                }
            })
            .catch((error) => clearAndAddHttpError(error));
    }, []);

    const priceOf = (key: keyof Options['prices']): number => options?.prices[key]?.price_kes ?? 0;

    const total = useMemo(() => {
        if (!options) return 0;
        const t =
            (config.memory / 1024) * priceOf('ram') +
            (config.disk / 1024) * priceOf('disk') +
            (config.cpu / 100) * priceOf('cpu') +
            config.databases * priceOf('database') +
            config.backups * priceOf('backup') +
            config.allocations * priceOf('allocation');
        return Math.round(t * 100) / 100;
    }, [config, options]);

    const cap = options?.price_cap ?? 0;

    const clampField = (field: keyof Config, rawValue: number, unitPrice: number, scale: number): number => {
        if (!options || cap <= 0 || unitPrice <= 0) return Math.max(0, rawValue);

        const otherFieldsCost = total - ((config[field] as number) / scale) * unitPrice;
        const remaining = Math.max(0, cap - otherFieldsCost);
        const maxUnits = (remaining / unitPrice) * scale;

        return Math.max(0, Math.min(rawValue, Math.floor(maxUnits)));
    };

    const updateField = (field: keyof Config, value: number) => {
        setConfig((c) => {
            const next = { ...c };
            switch (field) {
                case 'memory':
                    next.memory = clampField('memory', value, priceOf('ram'), 1024);
                    break;
                case 'disk':
                    next.disk = clampField('disk', value, priceOf('disk'), 1024);
                    break;
                case 'cpu':
                    next.cpu = clampField('cpu', value, priceOf('cpu'), 100);
                    break;
                case 'databases':
                    next.databases = clampField('databases', value, priceOf('database'), 1);
                    break;
                case 'backups':
                    next.backups = clampField('backups', value, priceOf('backup'), 1);
                    break;
                case 'allocations':
                    next.allocations = Math.max(1, clampField('allocations', value, priceOf('allocation'), 1) || 1);
                    break;
            }
            return next;
        });
    };

    const onPurchase = () => {
        if (!config.eggId) return;

        setPurchasing(true);
        clearFlashes();

        http.post('/account/store/custom/purchase', {
            egg_id: config.eggId,
            memory: config.memory,
            disk: config.disk,
            cpu: config.cpu,
            databases: config.databases,
            backups: config.backups,
            allocations: config.allocations,
        })
            .then((response) => {
                if (response.data?.server_id) {
                    history.push(`/server/${response.data.server_id}`);
                    return;
                }
                setPurchasing(false);
                setConfirmOpen(false);
            })
            .catch((error) => {
                setPurchasing(false);
                setConfirmOpen(false);
                const message = error?.response?.data?.error;
                if (message) {
                    setErrorDialog({ message, showTopUp: message.toLowerCase().includes('insufficient') });
                } else {
                    clearAndAddHttpError(error);
                }
            });
    };

    if (!options) {
        return <Spinner centered size={'large'} />;
    }

    const usagePercent = cap > 0 ? Math.min(100, (total / cap) * 100) : 0;

    return (
        <div>
            <FlashMessageRender byKey={'account:custom-build'} css={tw`mb-4`} />

            <div css={tw`bg-neutral-800 border border-neutral-700 rounded-lg p-6`}>
                <div css={tw`mb-5`}>
                    <label css={tw`text-xs uppercase tracking-wide text-neutral-400 block mb-1`}>Game / Software</label>
                    <select
                        css={tw`w-full bg-neutral-900 border border-neutral-600 rounded px-3 py-2 text-neutral-100 text-sm`}
                        value={config.eggId ?? ''}
                        onChange={(e) => setConfig((c) => ({ ...c, eggId: Number(e.target.value) }))}
                    >
                        {Object.entries(options.eggs).map(([nestName, eggs]) => (
                            <optgroup key={nestName} label={nestName}>
                                {eggs.map((egg) => (
                                    <option key={egg.id} value={egg.id}>
                                        {egg.name}
                                    </option>
                                ))}
                            </optgroup>
                        ))}
                    </select>
                </div>

                <div css={tw`grid grid-cols-1 sm:grid-cols-2 gap-5`}>
                    <FieldInput
                        label={`Memory (MB) — KSh ${priceOf('ram')} per GB`}
                        value={config.memory}
                        step={128}
                        onChange={(v) => updateField('memory', v)}
                    />
                    <FieldInput
                        label={`Disk (MB) — KSh ${priceOf('disk')} per GB`}
                        value={config.disk}
                        step={512}
                        onChange={(v) => updateField('disk', v)}
                    />
                    <FieldInput
                        label={`CPU (%) — KSh ${priceOf('cpu')} per 100%`}
                        value={config.cpu}
                        step={25}
                        onChange={(v) => updateField('cpu', v)}
                    />
                    <FieldInput
                        label={`Allocations — KSh ${priceOf('allocation')} per port`}
                        value={config.allocations}
                        step={1}
                        onChange={(v) => updateField('allocations', v)}
                    />
                    <FieldInput
                        label={`Databases — KSh ${priceOf('database')} each`}
                        value={config.databases}
                        step={1}
                        onChange={(v) => updateField('databases', v)}
                    />
                    <FieldInput
                        label={`Backups — KSh ${priceOf('backup')} each`}
                        value={config.backups}
                        step={1}
                        onChange={(v) => updateField('backups', v)}
                    />
                </div>

                <div css={tw`mt-6 pt-5 border-t border-neutral-700`}>
                    <div css={tw`flex justify-between items-baseline mb-2`}>
                        <p css={tw`text-sm text-neutral-400`}>Total Price</p>
                        <p css={tw`text-2xl font-bold text-neutral-100`}>
                            KSh {total.toFixed(2)}
                            {cap > 0 && <span css={tw`text-xs text-neutral-500 font-normal`}> / {cap.toFixed(2)} max</span>}
                        </p>
                    </div>
                    {cap > 0 && (
                        <div css={tw`w-full h-2 bg-neutral-900 rounded-full overflow-hidden mb-5`}>
                            <div
                                css={[tw`h-full bg-cyan-500 transition-all duration-150`, { width: `${usagePercent}%` }]}
                            />
                        </div>
                    )}
                    <Button onClick={() => setConfirmOpen(true)} disabled={!config.eggId || total <= 0}>
                        Buy This Configuration
                    </Button>
                </div>
            </div>

            <Dialog.Confirm
                open={confirmOpen && !purchasing}
                onClose={() => setConfirmOpen(false)}
                title={'Confirm Purchase'}
                confirm={'Buy Now'}
                onConfirmed={onPurchase}
            >
                This will deduct KSh {total.toFixed(2)} from your wallet and immediately provision your custom
                server.
            </Dialog.Confirm>

            {purchasing && (
                <Dialog open title={'Provisioning your server'} onClose={() => undefined}>
                    <div css={tw`flex items-center gap-4`}>
                        <Spinner />
                        <p css={tw`text-sm text-neutral-300`}>Please wait, this can take a few moments...</p>
                    </div>
                </Dialog>
            )}

            <Dialog open={!!errorDialog} onClose={() => setErrorDialog(null)} title={'Purchase Failed'}>
                <p css={tw`text-sm text-neutral-300`}>{errorDialog?.message}</p>
                <Dialog.Footer>
                    <button
                        onClick={() => setErrorDialog(null)}
                        css={tw`text-sm text-neutral-400 hover:text-neutral-200 mr-4`}
                    >
                        Close
                    </button>
                    {errorDialog?.showTopUp && (
                        <Button onClick={() => history.push('/account/wallet')}>Top Up Now</Button>
                    )}
                </Dialog.Footer>
            </Dialog>
        </div>
    );
};

const FieldInput = ({
    label,
    value,
    step,
    onChange,
}: {
    label: string;
    value: number;
    step: number;
    onChange: (value: number) => void;
}) => (
    <div>
        <label css={tw`text-xs uppercase tracking-wide text-neutral-400 block mb-1`}>{label}</label>
        <input
            type={'number'}
            min={0}
            step={step}
            value={value}
            onChange={(e) => onChange(Number(e.target.value) || 0)}
            css={tw`w-full bg-neutral-900 border border-neutral-600 rounded px-3 py-2 text-neutral-100 text-sm`}
        />
    </div>
);
