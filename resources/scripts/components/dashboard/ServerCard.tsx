import React, { memo, useEffect, useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faHdd, faMemory, faMicrochip, faServer } from '@fortawesome/free-solid-svg-icons';
import { Link } from 'react-router-dom';
import { Server } from '@/api/server/getServer';
import getServerResourceUsage, { ServerPowerState, ServerStats } from '@/api/server/getServerResourceUsage';
import { bytesToString } from '@/lib/formatters';
import tw from 'twin.macro';
import Spinner from '@/components/elements/Spinner';
import styled from 'styled-components/macro';
import isEqual from 'react-fast-compare';

const isAlarmState = (current: number, limit: number): boolean => limit > 0 && current / (limit * 1024 * 1024) >= 0.9;

const Icon = memo(
    styled(FontAwesomeIcon)<{ $alarm: boolean }>`
        ${(props) => (props.$alarm ? tw`text-red-400` : tw`text-neutral-500`)};
    `,
    isEqual
);

const Card = styled(Link)<{ $status: ServerPowerState | undefined }>`
    ${tw`bg-neutral-800 border border-neutral-700 rounded-lg p-5 no-underline flex flex-col relative overflow-hidden transition-colors duration-150`};

    &:hover {
        ${tw`border-neutral-500`};
    }

    &::before {
        content: '';
        ${tw`absolute top-0 left-0 right-0 h-1`};
        ${({ $status }) =>
            !$status || $status === 'offline'
                ? tw`bg-red-500`
                : $status === 'running'
                ? tw`bg-green-500`
                : tw`bg-yellow-500`};
    }
`;

type Timer = ReturnType<typeof setInterval>;

export default ({ server }: { server: Server }) => {
    const interval = useRef<Timer>(null) as React.MutableRefObject<Timer>;
    const [isSuspended, setIsSuspended] = useState(server.status === 'suspended');
    const [stats, setStats] = useState<ServerStats | null>(null);

    const getStats = () =>
        getServerResourceUsage(server.uuid)
            .then((data) => setStats(data))
            .catch((error) => console.error(error));

    useEffect(() => {
        setIsSuspended(stats?.isSuspended || server.status === 'suspended');
    }, [stats?.isSuspended, server.status]);

    useEffect(() => {
        if (isSuspended || server.isNodeUnderMaintenance) return;

        getStats().then(() => {
            interval.current = setInterval(() => getStats(), 30000);
        });

        return () => {
            interval.current && clearInterval(interval.current);
        };
    }, [isSuspended, server.isNodeUnderMaintenance]);

    const alarms = { cpu: false, memory: false, disk: false };
    if (stats) {
        alarms.cpu = server.limits.cpu === 0 ? false : stats.cpuUsagePercent >= server.limits.cpu * 0.9;
        alarms.memory = isAlarmState(stats.memoryUsageInBytes, server.limits.memory);
        alarms.disk = server.limits.disk === 0 ? false : isAlarmState(stats.diskUsageInBytes, server.limits.disk);
    }

    return (
        <Card to={`/server/${server.id}`} $status={stats?.status}>
            <div css={tw`flex items-center mb-4`}>
                <div css={tw`w-9 h-9 rounded-lg bg-neutral-900 flex items-center justify-center mr-3 flex-shrink-0`}>
                    <FontAwesomeIcon icon={faServer} css={tw`text-neutral-400`} />
                </div>
                <p css={tw`text-neutral-100 font-medium break-words line-clamp-1`}>{server.name}</p>
            </div>

            {isSuspended ? (
                <span css={tw`bg-red-500 rounded px-2 py-1 text-red-100 text-xs self-start`}>
                    {server.status === 'suspended' ? 'Suspended' : 'Connection Error'}
                </span>
            ) : server.isNodeUnderMaintenance ? (
                <span css={tw`bg-yellow-500 rounded px-2 py-1 text-yellow-100 text-xs self-start`}>
                    Under Maintenance
                </span>
            ) : !stats ? (
                <Spinner size={'small'} />
            ) : (
                <div css={tw`flex justify-between mt-1`}>
                    <div css={tw`flex items-center`}>
                        <Icon icon={faMicrochip} $alarm={alarms.cpu} css={tw`text-xs mr-1`} />
                        <p css={tw`text-xs text-neutral-400`}>{stats.cpuUsagePercent.toFixed(0)}%</p>
                    </div>
                    <div css={tw`flex items-center`}>
                        <Icon icon={faMemory} $alarm={alarms.memory} css={tw`text-xs mr-1`} />
                        <p css={tw`text-xs text-neutral-400`}>{bytesToString(stats.memoryUsageInBytes)}</p>
                    </div>
                    <div css={tw`flex items-center`}>
                        <Icon icon={faHdd} $alarm={alarms.disk} css={tw`text-xs mr-1`} />
                        <p css={tw`text-xs text-neutral-400`}>{bytesToString(stats.diskUsageInBytes)}</p>
                    </div>
                </div>
            )}
        </Card>
    );
};
