import React, { useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import PageContentBlock from '@/components/elements/PageContentBlock';
import Spinner from '@/components/elements/Spinner';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import http from '@/api/http';
import tw from 'twin.macro';
import { useFlashKey } from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import CustomBuildContainer from '@/components/dashboard/CustomBuildContainer';

interface Plan {
    id: number;
    name: string;
    description: string | null;
    price: string;
    currency: string;
    billing_period: string;
    memory: number;
    disk: number;
    cpu: number;
    databases: number;
    backups: number;
    allocations: number;
    is_featured: boolean;
}

export default () => {
    const history = useHistory();
    const [tab, setTab] = useState<'plans' | 'custom'>('plans');
    const [plans, setPlans] = useState<Plan[] | null>(null);
    const [selected, setSelected] = useState<Plan | null>(null);
    const [purchasing, setPurchasing] = useState(false);
    const [errorDialog, setErrorDialog] = useState<{ title: string; message: string; showTopUp: boolean } | null>(null);
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('account:store');

    useEffect(() => {
        clearFlashes();
        http.get('/account/store/plans')
            .then((response) => setPlans(response.data))
            .catch((error) => clearAndAddHttpError(error));
    }, []);

    const onConfirmPurchase = () => {
        if (!selected) return;

        setPurchasing(true);
        clearFlashes();

        http.post(`/account/store/purchase/${selected.id}`)
            .then((response) => {
                if (response.data?.server_id) {
                    history.push(`/server/${response.data.server_id}`);
                    return;
                }
                setPurchasing(false);
                setSelected(null);
            })
            .catch((error) => {
                setPurchasing(false);
                const message = error?.response?.data?.error;
                if (message) {
                    setErrorDialog({
                        title: 'Purchase Failed',
                        message,
                        showTopUp: message.toLowerCase().includes('insufficient'),
                    });
                } else {
                    clearAndAddHttpError(error);
                }
                setSelected(null);
            });
    };

    return (
        <PageContentBlock title={'Available Servers'}>
            <FlashMessageRender byKey={'account:store'} css={tw`mb-4`} />

            <div css={tw`flex gap-2 mb-6`}>
                <button
                    onClick={() => setTab('plans')}
                    css={[
                        tw`px-4 py-2 rounded-lg text-sm font-medium border`,
                        tab === 'plans'
                            ? tw`bg-cyan-600 border-cyan-600 text-white`
                            : tw`bg-neutral-900 border-neutral-600 text-neutral-300 hover:border-neutral-400`,
                    ]}
                >
                    Fixed Plans
                </button>
                <button
                    onClick={() => setTab('custom')}
                    css={[
                        tw`px-4 py-2 rounded-lg text-sm font-medium border`,
                        tab === 'custom'
                            ? tw`bg-cyan-600 border-cyan-600 text-white`
                            : tw`bg-neutral-900 border-neutral-600 text-neutral-300 hover:border-neutral-400`,
                    ]}
                >
                    Build Your Own
                </button>
            </div>

            {tab === 'custom' && <CustomBuildContainer />}

            {tab === 'plans' && (!plans ? (
                <Spinner centered size={'large'} />
            ) : plans.length === 0 ? (
                <p css={tw`text-sm text-neutral-400`}>No plans are available for purchase right now.</p>
            ) : (
                <div css={tw`grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4`}>
                    {plans.map((plan) => (
                        <div
                            key={plan.id}
                            css={[
                                tw`bg-neutral-800 border rounded-lg p-5 flex flex-col relative`,
                                plan.is_featured ? tw`border-cyan-500` : tw`border-neutral-700`,
                            ]}
                        >
                            {plan.is_featured && (
                                <span
                                    css={tw`absolute -top-2 left-1/2 transform -translate-x-1/2 bg-cyan-600 text-white text-xs font-bold px-2 py-0.5 rounded-full`}
                                >
                                    POPULAR
                                </span>
                            )}
                            <p css={tw`text-neutral-100 font-bold mb-1`}>{plan.name}</p>
                            <p css={tw`text-xs text-neutral-400 mb-3 min-h-[16px]`}>{plan.description}</p>
                            <p css={tw`text-xl font-bold text-neutral-100 mb-1`}>
                                {plan.currency} {parseFloat(plan.price).toFixed(2)}
                                <span css={tw`text-xs text-neutral-400 font-normal block`}>
                                    / {plan.billing_period}
                                </span>
                            </p>
                            <ul css={tw`text-xs text-neutral-400 border-t border-neutral-700 mt-3 pt-3 mb-4`}>
                                <li css={tw`flex justify-between py-0.5`}>
                                    <span>Memory</span>
                                    <span css={tw`text-neutral-200`}>{plan.memory} MB</span>
                                </li>
                                <li css={tw`flex justify-between py-0.5`}>
                                    <span>Disk</span>
                                    <span css={tw`text-neutral-200`}>{plan.disk} MB</span>
                                </li>
                                <li css={tw`flex justify-between py-0.5`}>
                                    <span>CPU</span>
                                    <span css={tw`text-neutral-200`}>{plan.cpu}%</span>
                                </li>
                                <li css={tw`flex justify-between py-0.5`}>
                                    <span>Databases</span>
                                    <span css={tw`text-neutral-200`}>{plan.databases}</span>
                                </li>
                                <li css={tw`flex justify-between py-0.5`}>
                                    <span>Backups</span>
                                    <span css={tw`text-neutral-200`}>{plan.backups}</span>
                                </li>
                            </ul>
                            <Button css={tw`mt-auto`} onClick={() => setSelected(plan)}>
                                Buy Now
                            </Button>
                        </div>
                    ))}
                </div>
            ))}

            <Dialog.Confirm
                open={!!selected && !purchasing}
                onClose={() => setSelected(null)}
                title={'Confirm Purchase'}
                confirm={'Buy Now'}
                onConfirmed={onConfirmPurchase}
            >
                {selected && (
                    <>
                        This will deduct {selected.currency} {parseFloat(selected.price).toFixed(2)} from your
                        wallet and immediately provision a new server on the &quot;{selected.name}&quot; plan.
                    </>
                )}
            </Dialog.Confirm>

            {purchasing && (
                <Dialog open title={'Provisioning your server'} onClose={() => undefined}>
                    <div css={tw`flex items-center gap-4`}>
                        <Spinner />
                        <p css={tw`text-sm text-neutral-300`}>
                            Please wait, this can take a few moments...
                        </p>
                    </div>
                </Dialog>
            )}

            <Dialog open={!!errorDialog} onClose={() => setErrorDialog(null)} title={errorDialog?.title || 'Error'}>
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
        </PageContentBlock>
    );
};
