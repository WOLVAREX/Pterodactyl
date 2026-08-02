import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Server } from '@/api/server/getServer';
import getServers from '@/api/getServers';
import ServerCard from '@/components/dashboard/ServerCard';
import Spinner from '@/components/elements/Spinner';
import PageContentBlock from '@/components/elements/PageContentBlock';
import ContentBox from '@/components/elements/ContentBox';
import http from '@/api/http';
import tw from 'twin.macro';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    Tooltip as ChartTooltip,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
    Legend,
} from 'recharts';

interface Transaction {
    id: number;
    type: string;
    amount: string;
    status: string;
    created_at: string;
}

const STATUS_COLORS: Record<string, string> = {
    success: '#22c55e',
    pending: '#eab308',
    failed: '#ef4444',
};

const StatCard = ({ label, value }: { label: string; value: React.ReactNode }) => (
    <div css={tw`bg-neutral-800 border border-neutral-700 rounded-lg px-6 py-5 flex-1`}>
        <p css={tw`text-xs uppercase tracking-wide text-neutral-400 mb-2`}>{label}</p>
        <p css={tw`text-2xl font-bold text-neutral-100`}>{value}</p>
    </div>
);

export default () => {
    const [servers, setServers] = useState<Server[] | null>(null);
    const [totalServers, setTotalServers] = useState(0);
    const [balance, setBalance] = useState(0);
    const [transactions, setTransactions] = useState<Transaction[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        Promise.all([
            getServers({ page: 1 }),
            http.get('/account/wallet/data'),
        ])
            .then(([serverResult, walletResult]) => {
                setServers(serverResult.items.slice(0, 3));
                setTotalServers(serverResult.pagination.total);
                setBalance(walletResult.data.balance ?? 0);
                setTransactions(walletResult.data.transactions ?? []);
            })
            .finally(() => setLoading(false));
    }, []);

    const chartData = Object.values(
        transactions
            .filter((t) => t.type === 'deposit' && t.status === 'success')
            .reduce<Record<string, { date: string; amount: number }>>((acc, t) => {
                const date = new Date(t.created_at).toLocaleDateString(undefined, {
                    month: 'short',
                    day: 'numeric',
                });
                acc[date] = acc[date] || { date, amount: 0 };
                acc[date].amount += parseFloat(t.amount);
                return acc;
            }, {})
    ).slice(-7);

    const statusCounts = transactions.reduce<Record<string, number>>((acc, t) => {
        acc[t.status] = (acc[t.status] || 0) + 1;
        return acc;
    }, {});

    const pieData = Object.entries(statusCounts).map(([status, count]) => ({
        name: status,
        value: count,
    }));

    if (loading) {
        return (
            <PageContentBlock title={'Dashboard'}>
                <Spinner centered size={'large'} />
            </PageContentBlock>
        );
    }

    return (
        <PageContentBlock title={'Dashboard'}>
            <div css={tw`flex flex-col sm:flex-row gap-4 mb-8`}>
                <StatCard label={'Wallet Balance'} value={`KSh ${balance.toLocaleString(undefined, { minimumFractionDigits: 2 })}`} />
                <StatCard label={'Total Servers'} value={totalServers} />
                <StatCard label={'Transactions'} value={transactions.length} />
            </div>

            <div css={tw`flex items-center justify-between mb-4`}>
                <h2 css={tw`text-lg font-header text-neutral-100`}>Your Servers</h2>
                <Link to={'/servers'} css={tw`text-sm text-cyan-400 no-underline hover:text-cyan-300`}>
                    View All →
                </Link>
            </div>

            {!servers || servers.length === 0 ? (
                <p css={tw`text-sm text-neutral-400 mb-8`}>You don&apos;t have any servers yet.</p>
            ) : (
                <div css={tw`grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8`}>
                    {servers.map((server) => (
                        <ServerCard key={server.uuid} server={server} />
                    ))}
                </div>
            )}

            <div css={tw`flex flex-col lg:flex-row gap-4`}>
                <ContentBox title={'Deposits (Recent Activity)'} css={tw`flex-1`}>
                    {chartData.length === 0 ? (
                        <p css={tw`text-sm text-neutral-400`}>No successful deposits yet.</p>
                    ) : (
                        <ResponsiveContainer width={'100%'} height={220}>
                            <BarChart data={chartData}>
                                <XAxis dataKey={'date'} stroke={'#7a8ba3'} fontSize={12} />
                                <YAxis stroke={'#7a8ba3'} fontSize={12} />
                                <ChartTooltip
                                    contentStyle={{ background: '#1f2937', border: 'none', borderRadius: 6 }}
                                    labelStyle={{ color: '#fff' }}
                                />
                                <Bar dataKey={'amount'} fill={'#06b6d4'} radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    )}
                </ContentBox>

                <ContentBox title={'Transaction Status'} css={tw`flex-1`}>
                    {pieData.length === 0 ? (
                        <p css={tw`text-sm text-neutral-400`}>No transactions yet.</p>
                    ) : (
                        <ResponsiveContainer width={'100%'} height={220}>
                            <PieChart>
                                <Pie data={pieData} dataKey={'value'} nameKey={'name'} innerRadius={45} outerRadius={75}>
                                    {pieData.map((entry) => (
                                        <Cell key={entry.name} fill={STATUS_COLORS[entry.name] || '#7a8ba3'} />
                                    ))}
                                </Pie>
                                <Legend wrapperStyle={{ fontSize: 12, color: '#d6e4f0' }} />
                                <ChartTooltip contentStyle={{ background: '#1f2937', border: 'none', borderRadius: 6 }} />
                            </PieChart>
                        </ResponsiveContainer>
                    )}
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
