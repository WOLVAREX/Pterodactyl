import React, { useEffect, useState } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import ContentBox from '@/components/elements/ContentBox';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import { Formik, Form, FormikHelpers } from 'formik';
import { object, number } from 'yup';
import http from '@/api/http';
import tw from 'twin.macro';
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

interface Values {
    amount: number;
}

const statusColor = (status: string): string => {
    if (status === 'success') return '#22c55e';
    if (status === 'failed') return '#ef4444';
    return '#eab308';
};

export default () => {
    const [data, setData] = useState<WalletData | null>(null);
    const [loading, setLoading] = useState(true);
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('account:wallet');

    const load = () => {
        http.get('/account/wallet/data')
            .then((response) => setData(response.data))
            .catch((error) => clearAndAddHttpError(error))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        clearFlashes();
        load();
    }, []);

    const onSubmit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes();

        http.post('/account/wallet/topup', { amount: values.amount })
            .then((response) => {
                if (response.data?.authorization_url) {
                    window.location.href = response.data.authorization_url;
                    return;
                }

                setSubmitting(false);
            })
            .catch((error) => {
                setSubmitting(false);
                clearAndAddHttpError(error);
            });
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
                        initialValues={{ amount: 100 }}
                        validationSchema={object().shape({
                            amount: number()
                                .min(10, 'Minimum top-up is KSh 10.')
                                .required('Please enter an amount.'),
                        })}
                    >
                        {({ isSubmitting }) => (
                            <Form>
                                <div css={tw`flex items-end gap-4`}>
                                    <div css={tw`flex-1`}>
                                        <Field type={'number'} name={'amount'} label={'Amount (KSh)'} />
                                    </div>
                                    <Button type={'submit'} isLoading={isSubmitting} disabled={isSubmitting}>
                                        Top Up via Paystack
                                    </Button>
                                </div>
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
