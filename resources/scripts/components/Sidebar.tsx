import * as React from 'react';
import { useState } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faCogs,
    faTachometerAlt,
    faServer,
    faWallet,
    faSignOutAlt,
    faBars,
    faTimes,
    faUser,
    faKey,
    faTerminal,
    faHistory,
    faShoppingCart,
} from '@fortawesome/free-solid-svg-icons';
import routes from '@/routers/routes';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import tw from 'twin.macro';
import styled from 'styled-components/macro';
import http from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Avatar from '@/components/Avatar';

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

const ACCOUNT_ICONS: Record<string, any> = {
    Account: faUser,
    'API Credentials': faKey,
    'SSH Keys': faTerminal,
    Activity: faHistory,
    Wallet: faWallet,
};

export default () => {
    const name = useStoreState((state: ApplicationStore) => state.settings.data!.name);
    const rootAdmin = useStoreState((state: ApplicationStore) => state.user.data!.rootAdmin);
    const [isLoggingOut, setIsLoggingOut] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    const onTriggerLogout = () => {
        setIsLoggingOut(true);
        http.post('/auth/logout').finally(() => {
            // @ts-expect-error this is valid
            window.location = '/';
        });
    };

    return (
        <>
            <SpinnerOverlay visible={isLoggingOut} />

            <MobileHeader>
                <button onClick={() => setMobileOpen(true)} css={tw`text-neutral-300 text-xl mr-4`}>
                    <FontAwesomeIcon icon={faBars} />
                </button>
                <Link to={'/'} css={tw`text-lg font-header font-medium text-neutral-100 no-underline`}>
                    {name}
                </Link>
            </MobileHeader>

            <Backdrop $open={mobileOpen} onClick={() => setMobileOpen(false)} />

            <SidebarWrapper $open={mobileOpen}>
                <div css={tw`flex items-center justify-between px-5 py-5`}>
                    <Link
                        to={'/'}
                        css={tw`text-xl font-header font-medium text-neutral-100 no-underline`}
                        onClick={() => setMobileOpen(false)}
                    >
                        {name}
                    </Link>
                    <button
                        onClick={() => setMobileOpen(false)}
                        css={tw`text-neutral-400 text-lg lg:hidden`}
                    >
                        <FontAwesomeIcon icon={faTimes} />
                    </button>
                </div>

                <nav css={tw`flex-1 flex flex-col gap-1 mt-2`}>
                    <NavItem to={'/'} exact onClick={() => setMobileOpen(false)}>
                        <FontAwesomeIcon icon={faTachometerAlt} css={tw`w-4`} />
                        Dashboard
                    </NavItem>
                    <NavItem to={'/servers'} onClick={() => setMobileOpen(false)}>
                        <FontAwesomeIcon icon={faServer} css={tw`w-4`} />
                        My Servers
                    </NavItem>
                    <NavItem to={'/store'} onClick={() => setMobileOpen(false)}>
                        <FontAwesomeIcon icon={faShoppingCart} css={tw`w-4`} />
                        Available Servers
                    </NavItem>

                    <p css={tw`text-xs uppercase tracking-wide text-neutral-500 px-4 mt-4 mb-1`}>Account</p>
                    {routes.account
                        .filter((route) => !!route.name)
                        .map((route) => (
                            <NavItem
                                key={route.path}
                                to={`/account/${route.path}`.replace('//', '/')}
                                exact={route.path === '' || route.path === '/'}
                                onClick={() => setMobileOpen(false)}
                            >
                                <FontAwesomeIcon icon={ACCOUNT_ICONS[route.name!] || faCogs} css={tw`w-4`} />
                                {route.name}
                            </NavItem>
                        ))}
                    {rootAdmin && (
                        
                        <a
                            href={'/admin'}
                            rel={'noreferrer'}
                            css={tw`flex items-center gap-3 px-4 py-3 mx-3 rounded-lg text-neutral-300 no-underline text-sm hover:bg-neutral-800 hover:text-neutral-100 transition-colors duration-150`}
                        >
                            <FontAwesomeIcon icon={faCogs} css={tw`w-4`} />
                            Admin
                        </a>
                    )}
                </nav>

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
