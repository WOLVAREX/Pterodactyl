import React, { useEffect, useRef, useState } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import ContentBox from '@/components/elements/ContentBox';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import { Formik, Form, FormikHelpers } from 'formik';
import { object, string, number } from 'yup';
import http from '@/api/http';
import tw from 'twin.macro';
import styled from 'styled-components/macro';
import { useFlashKey } from '@/plugins/useFlash';
import Spinner from '@/components/elements/Spinner';

interface Transaction {
    id: number;
    type: string;
    amount: string;
    status: string;
    description: string | null;
    created_at: string;
}

interface WalletData {
    balance: number;
    transactions: Transaction[];
}

type PaymentMethod = 'phone' | 'card';

interface Values {
    method: PaymentMethod;
    amount: number;
    phone: string;
}

declare global {
    interface Window {
        PaystackPop?: {
            setup: (options: Record<string, any>) => { openIframe: () => void };
        };
    }
}

const PAYSTACK_SCRIPT_ID = 'paystack-inline-js';
const PAYSTACK_SCRIPT_SRC = 'https://js.paystack.co/v1/inline.js';
const POLL_INTERVAL_MS = 3000;
const MAX_POLL_ATTEMPTS = 40; // ~2 minutes

const loadPaystackScript = (): Promise<void> =>
    new Promise((resolve, reject) => {
        if (window.PaystackPop) {
            resolve();
            return;
        }

        const existing = document.getElementById(PAYSTACK_SCRIPT_ID) as HTMLScriptElement | null;
        if (existing) {
            existing.addEventListener('load', () => resolve());
            existing.addEventListener('error', () => reject(new Error('Failed to load Paystack.')));
            return;
        }

        const script = document.createElement('script');
        script.id = PAYSTACK_SCRIPT_ID;
        script.src = PAYSTACK_SCRIPT_SRC;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Paystack.'));
        document.head.appendChild(script);
    });

const statusColor = (status: string): string => {
    if (status === 'success') return '#22c55e';
    if (status === 'failed') return '#ef4444';
    return '#eab308';
};

const MethodToggle = styled.div`
    ${tw`flex gap-2 mb-4`};
`;

const MethodButton = styled.button<{ $active: boolean }>`
    ${tw`flex-1 rounded-lg border px-4 py-3 text-sm font-medium transition-colors duration-150 cursor-pointer`};
    ${(props) =>
        props.$active
            ? tw`bg-cyan-600 border-cyan-600 text-white`
            : tw`bg-neutral-900 border-neutral-600 text-neutral-300 hover:border-neutral-400`};
`;

export default () => {
    const [data, setData] = useState<WalletData | null>(null);
    const [loading, setLoading] = useState(true);
    const [pendingMessage, setPendingMessage] = useState<string | null>(null);
    const { clearFlashes, clearAndAddHttpError, addError } = useFlashKey('account:wallet');
    const pollTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    const load = () => {
        http.get('/account/wallet/data')
            .then((response) => setData(response.data))
            .catch((error) => clearAndAddHttpError(error))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        clearFlashes();
        load();

        return () => {
            if (pollTimeout.current) {
                clearTimeout(pollTimeout.current);
            }
        };
    }, []);

    const pollStatus = (reference: string, attempt = 0): Promise<'success' | 'failed'> =>
        new Promise((resolve) => {
            http.get(`/account/wallet/topup/status/${reference}`)
                .then(({ data: statusData }) => {
                    if (statusData.status === 'success') {
                        resolve('success');
                        return;
                    }

                    if (statusData.status === 'failed') {
                        resolve('failed');
                        return;
                    }

                    if (attempt >= MAX_POLL_ATTEMPTS) {
                        resolve('failed');
                        return;
                    }

                    pollTimeout.current = setTimeout(() => {
                        pollStatus(reference, attempt + 1).then(resolve);
                    }, POLL_INTERVAL_MS);
                })
                .catch(() => {
                    if (attempt >= MAX_POLL_ATTEMPTS) {
                        resolve('failed');
                        return;
                    }

                    pollTimeout.current = setTimeout(() => {
                        pollStatus(reference, attempt + 1).then(resolve);
                    }, POLL_INTERVAL_MS);
                });
        });

    const finishUp = (outcome: 'success' | 'failed', successText: string, failureText: string) => {
        setPendingMessage(null);

        if (outcome === 'success') {
            load();
        } else {
            addError(failureText);
        }

        return outcome === 'success' ? successText : failureText;
    };

    const submitMobileMoney = async (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        try {
            const { data: initData } = await http.post('/account/wallet/topup/mobile-money', {
                amount: values.amount,
                phone: values.phone,
            });

            setPendingMessage(initData.message || 'Enter your M-Pesa PIN on your phone to complete this payment.');

            const outcome = await pollStatus(initData.reference);
            finishUp(
                outcome,
                'Payment received — your wallet has been topped up.',
                'We could not confirm this payment. If you completed the STK push, check your transaction history shortly.'
            );
        } catch (error) {
            const err = error as Error;
            setPendingMessage(null);
            clearAndAddHttpError(err);
        } finally {
            setSubmitting(false);
        }
    };

    const submitCard = async (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        try {
            await loadPaystackScript();

            const { data: initData } = await http.post('/account/wallet/topup/card', {
                amount: values.amount,
            });

            if (!window.PaystackPop) {
                throw new Error('Paystack failed to load. Please refresh and try again.');
            }

            setPendingMessage('Complete your card details in the popup to finish this payment.');

            const handler = window.PaystackPop.setup({
                key: initData.public_key,
                email: initData.email,
                amount: initData.amount,
                currency: 'KES',
                ref: initData.reference,
                channels: ['card'],
                callback: (response: { reference?: string }) => {
                    pollStatus(response.reference || initData.reference).then((outcome) => {
                        finishUp(
                            outcome,
                            'Payment received — your wallet has been topped up.',
                            'We could not confirm this payment. Please check your transaction history shortly.'
                        );
                        setSubmitting(false);
                    });
                },
                onClose: () => {
                    setPendingMessage(null);
                    setSubmitting(false);
                },
            });

            handler.openIframe();
        } catch (error) {
            const err = error as Error;
            setPendingMessage(null);
            clearAndAddHttpError(err);
            setSubmitting(false);
        }
    };

    const onSubmit = (values: Values, helpers: FormikHelpers<Values>) => {
        clearFlashes();
        setPendingMessage(null);

        if (values.method === 'card') {
            submitCard(values, helpers);
        } else {
            submitMobileMoney(values, helpers);
        }
    };

    if (loading) {
        return (
            <PageContentBlock title={'Wallet'}>
                <Spinner centered />
            </PageContentBlock>
        );
    }

    return (
        <PageContentBlock title={'Wallet'}>
            <div css={tw`flex flex-wrap`}>
                <ContentBox title={'Balance'} showFlashes={'account:wallet'} css={tw`w-full sm:w-1/3 sm:mr-8`}>
                    <p css={tw`text-4xl font-bold text-neutral-100`}>
                        KSh {(data?.balance ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </p>
                    <p css={tw`text-sm text-neutral-400 mt-1`}>Available wallet balance</p>
                </ContentBox>

                <ContentBox title={'Top Up'} css={tw`w-full sm:flex-1 mt-8 sm:mt-0`}>
                    <Formik
                        onSubmit={onSubmit}
                        initialValues={{ method: 'phone', amount: 100, phone: '' } as Values}
                        validationSchema={object().shape({
                            amount: number()
                                .min(10, 'Minimum top-up is KSh 10.')
                                .required('Please enter an amount.'),
                            phone: string().when('method', {
                                is: 'phone',
                                then: string()
                                    .required('Enter the phone number to receive the STK push on.')
                                    .matches(
                                        /^(?:\+?254|0)?[71]\d{8}$/,
                                        'Enter a valid Safaricom number, e.g. 0712345678.'
                                    ),
                                otherwise: string().notRequired(),
                            }),
                        })}
                    >
                        {({ isSubmitting, values, setFieldValue }) => (
                            <Form>
                                <MethodToggle>
                                    <MethodButton
                                        type={'button'}
                                        $active={values.method === 'phone'}
                                        disabled={isSubmitting}
                                        onClick={() => {
                                            setPendingMessage(null);
                                            setFieldValue('method', 'phone');
                                        }}
                                    >
                                        Phone Number (M-Pesa)
                                    </MethodButton>
                                    <MethodButton
                                        type={'button'}
                                        $active={values.method === 'card'}
                                        disabled={isSubmitting}
                                        onClick={() => {
                                            setPendingMessage(null);
                                            setFieldValue('method', 'card');
                                        }}
                                    >
                                        Card
                                    </MethodButton>
                                </MethodToggle>

                                <div css={tw`flex flex-col sm:flex-row sm:items-end gap-4`}>
                                    <div css={tw`w-full sm:w-40`}>
                                        <Field type={'number'} name={'amount'} label={'Amount (KSh)'} />
                                    </div>
                                    {values.method === 'phone' && (
                                        <div css={tw`flex-1`}>
                                            <Field
                                                type={'tel'}
                                                name={'phone'}
                                                label={'M-Pesa Phone Number'}
                                                placeholder={'0712345678'}
                                            />
                                        </div>
                                    )}
                                    <Button type={'submit'} isLoading={isSubmitting} disabled={isSubmitting}>
                                        {values.method === 'phone' ? 'Send STK Push' : 'Pay with Card'}
                                    </Button>
                                </div>

                                {pendingMessage && (
                                    <p css={tw`text-sm text-yellow-400 mt-3`}>{pendingMessage}</p>
                                )}
                            </Form>
                        )}
                    </Formik>
                </ContentBox>
            </div>

            <ContentBox title={'Transaction History'} css={tw`mt-8`}>
                {!data?.transactions.length ? (
                    <p css={tw`text-sm text-neutral-400`}>No transactions yet.</p>
                ) : (
                    <table css={tw`w-full text-sm`}>
                        <thead>
                            <tr css={tw`text-left text-neutral-400 border-b border-neutral-600`}>
                                <th css={tw`pb-2 font-normal`}>Date</th>
                                <th css={tw`pb-2 font-normal`}>Type</th>
                                <th css={tw`pb-2 font-normal`}>Description</th>
                                <th css={tw`pb-2 font-normal text-right`}>Amount</th>
                                <th css={tw`pb-2 font-normal text-right`}>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.transactions.map((tx) => (
                                <tr key={tx.id} css={tw`border-b border-neutral-700`}>
                                    <td css={tw`py-2 text-neutral-300`}>
                                        {new Date(tx.created_at).toLocaleString()}
                                    </td>
                                    <td css={tw`py-2 text-neutral-300 capitalize`}>{tx.type}</td>
                                    <td css={tw`py-2 text-neutral-300`}>{tx.description || '—'}</td>
                                    <td css={tw`py-2 text-neutral-100 text-right`}>
                                        KSh {parseFloat(tx.amount).toFixed(2)}
                                    </td>
                                    <td css={tw`py-2 text-right capitalize`}>
                                        <span style={{ color: statusColor(tx.status) }}>{tx.status}</span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </ContentBox>
        </PageContentBlock>
    );
};
