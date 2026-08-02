import React from 'react';
import { Route, Switch } from 'react-router-dom';
import Sidebar from '@/components/Sidebar';
import DashboardHomeContainer from '@/components/dashboard/DashboardHomeContainer';
import MyServersContainer from '@/components/dashboard/MyServersContainer';
import AvailableServersContainer from '@/components/dashboard/AvailableServersContainer';
import { NotFound } from '@/components/elements/ScreenBlock';
import TransitionRouter from '@/TransitionRouter';
import { useLocation } from 'react-router';
import Spinner from '@/components/elements/Spinner';
import routes from '@/routers/routes';
import tw from 'twin.macro';

export default () => {
    const location = useLocation();

    return (
        <>
            <Sidebar />
            <div css={tw`lg:ml-[21rem] mr-4 pt-20 lg:pt-4 pb-4`}>
                <TransitionRouter>
                    <React.Suspense fallback={<Spinner centered />}>
                        <Switch location={location}>
                            <Route path={'/'} exact>
                                <DashboardHomeContainer />
                            </Route>
                            <Route path={'/servers'} exact>
                                <MyServersContainer />
                            </Route>
                            <Route path={'/store'} exact>
                                <AvailableServersContainer />
                            </Route>
                            {routes.account.map(({ path, component: Component }) => (
                                <Route key={path} path={`/account/${path}`.replace('//', '/')} exact>
                                    <Component />
                                </Route>
                            ))}
                            <Route path={'*'}>
                                <NotFound />
                            </Route>
                        </Switch>
                    </React.Suspense>
                </TransitionRouter>
            </div>
        </>
    );
};
