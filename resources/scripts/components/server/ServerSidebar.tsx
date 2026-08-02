import * as React from 'react';
import { useState } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faArrowLeft,
    faArchive,
    faBars,
    faClock,
    faCogs,
    faDatabase,
    faExternalLinkAlt,
    faFolder,
    faHistory,
    faNetworkWired,
    faPlay,
    faSignOutAlt,
    faSlidersH,
    faTerminal,
    faTimes,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { ServerContext } from '@/state/server';
import tw from 'twin.macro';
import styled from 'styled-components/macro';
import http from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Avatar from '@/components/Avatar';
import Can from '@/components/elements/Can';
import SearchContainer from '@/components/dashboard/search/SearchContainer';

const SidebarWrapper = styled.div<{ $open: boolean }>`
    ${tw`fixed top-4 bottom-4 left-4 w-64 bg-neutral-900 rounded-xl shadow-lg flex flex-col z-40 transition-transform duration-200`};

    @media (max-width: 1023px) {
        ${tw`w-72`};
        transform: translateX(${(props) => (props.$open ? '0' : 'calc(-100% - 2rem)')});
    }
`;

const Backdrop = styled.div<{ $open: boolean }>`
    ${tw`fixed inset-0 bg-black bg-opacity-60 z-30 transition-opacity duration-200`};
    display: ${(props) => (props.$open ? 'block' : 'none')};

    @media (min-width: 1024px) {
        display: none;
    }
`;

const NavItem = styled(NavLink)`
    ${tw`flex items-center gap-3 px-4 py-3 mx-3 rounded-lg text-neutral-300 no-underline text-sm transition-colors duration-150`};

    &:hover {
        ${tw`bg-neutral-800 text-neutral-100`};
    }

    &.active {
        ${tw`bg-cyan-600 text-white`};
    }
`;

const MobileHeader = styled.div`
    ${tw`fixed top-0 left-0 right-0 h-14 bg-neutral-900 flex items-center px-4 z-20 shadow-md`};

    @media (min-width: 1024px) {
        display: none;
    }
`;

const Nav = styled.nav`
    ${tw`flex-1 flex flex-col gap-1 overflow-y-auto`};
    scrollbar-width: none;
    -ms-overflow-style: none;

    &::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }
`;

const SERVER_ROUTE_ICONS: Record<string, any> = {
    Console: faTerminal,
    Files: faFolder,
    Databases: faDatabase,
    Schedules: faClock,
    Users: faUsers,
    Backups: faArchive,
    Network: faNetworkWired,
    Startup: faPlay,
    Settings: faSlidersH,
    Activity: faHistory,
};

interface Route {
    path: string;
    name: string | undefined;
    permission: string | string[] | null;
    exact?: boolean;
}

export default ({ routes, to }: { routes: Route[]; to: (value: string, url?: boolean) => string }) => {
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user.data!.rootAdmin);
    const [isLoggingOut, setIsLoggingOut] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    const name = ServerContext.useStoreState((state) => state.server.data?.name);
    const serverId = ServerContext.useStoreState((state) => state.server.data?.internalId);

    const onTriggerLogout = () => {
        setIsLoggingOut(true);
        http.post('/auth/logout').finally(() => {
            // @ts-expect-error this is valid
            window.location = '/';
        });
    };

    const closeMobile = () => setMobileOpen(false);

    return (
        <>
            <SpinnerOverlay visible={isLoggingOut} />

            <MobileHeader>
                <button onClick={() => setMobileOpen(true)} css={tw`text-neutral-300 text-xl mr-4`}>
                    <FontAwesomeIcon icon={faBars} />
                </button>
                <span css={tw`text-lg font-header font-medium text-neutral-100 truncate`}>{name}</span>
            </MobileHeader>

            <Backdrop $open={mobileOpen} onClick={closeMobile} />

            <SidebarWrapper $open={mobileOpen}>
                <div css={tw`flex items-center justify-between px-5 py-5`}>
                    <Link
                        to={'/'}
                        css={tw`flex items-center gap-2 text-sm font-header font-medium text-neutral-400 hover:text-neutral-100 no-underline transition-colors duration-150`}
                        onClick={closeMobile}
                    >
                        <FontAwesomeIcon icon={faArrowLeft} css={tw`w-3`} />
                        Dashboard
                    </Link>
                    <div css={tw`flex items-center gap-3`}>
                        <span css={tw`text-neutral-400 hover:text-neutral-100 cursor-pointer transition-colors duration-150`}>
                            <SearchContainer />
                        </span>
                        <button onClick={closeMobile} css={tw`text-neutral-400 text-lg lg:hidden`}>
                            <FontAwesomeIcon icon={faTimes} />
                        </button>
                    </div>
                </div>

                <div css={tw`px-5 pb-4`}>
                    <p css={tw`text-lg font-header font-medium text-neutral-100 truncate leading-tight`}>{name}</p>
                </div>

                <Nav>
                    {routes
                        .filter((route) => !!route.name)
                        .map((route) =>
                            route.permission ? (
                                <Can key={route.path} action={route.permission} matchAny>
                                    <NavItem to={to(route.path, true)} exact={route.exact} onClick={closeMobile}>
                                        <FontAwesomeIcon
                                            icon={SERVER_ROUTE_ICONS[route.name!] || faCogs}
                                            css={tw`w-4`}
                                        />
                                        {route.name}
                                    </NavItem>
                                </Can>
                            ) : (
                                <NavItem
                                    key={route.path}
                                    to={to(route.path, true)}
                                    exact={route.exact}
                                    onClick={closeMobile}
                                >
                                    <FontAwesomeIcon icon={SERVER_ROUTE_ICONS[route.name!] || faCogs} css={tw`w-4`} />
                                    {route.name}
                                </NavItem>
                            )
                        )}
                    {rootAdmin && (
                        <a
                            href={`/admin/servers/view/${serverId}`}
                            target={'_blank'}
                            rel={'noreferrer'}
                            css={tw`flex items-center gap-3 px-4 py-3 mx-3 rounded-lg text-neutral-300 no-underline text-sm hover:bg-neutral-800 hover:text-neutral-100 transition-colors duration-150`}
                    >
                        <FontAwesomeIcon icon={faExternalLinkAlt} css={tw`w-4`} />
                        Manage in Admin
                        </a>
                    )}
                </Nav>

                <div css={tw`px-5 py-4 border-t border-neutral-800 flex items-center justify-between`}>
                    <div css={tw`flex items-center gap-2`}>
                        <span css={tw`flex items-center w-6 h-6`}>
                            <Avatar.User />
                        </span>
                    </div>
                    <button
                        onClick={onTriggerLogout}
                        css={tw`text-neutral-400 hover:text-neutral-100 transition-colors duration-150`}
                    >
                        <FontAwesomeIcon icon={faSignOutAlt} />
                    </button>
                </div>
            </SidebarWrapper>
        </>
    );
};
