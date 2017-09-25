// @flow
import './global.scss';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import React from 'react';
import Overlay from '../../components/Overlay';
import Router from '../../services/Router';
import SplitView from '../SplitView';
import Toolbar from '../Toolbar';
import ViewRenderer from '../ViewRenderer';
import applicationStyles from './application.scss';

const routes = [
    {
        name: 'Contacts',
        key: 'sulu_contact.contacts_list',
    },
    {
        name: 'Accounts',
        key: 'sulu_contact.accounts_list',
    },
    {
        name: 'Roles',
        key: 'sulu_security.list',
    },
    {
        name: 'Snippets',
        key: 'sulu_snippet.list',
    },
    {
        name: 'Tags',
        key: 'sulu_tag.list',
    },
];

type Props = {
    router: Router,
};

@observer
export default class Application extends React.PureComponent<Props> {
    @observable navigationOverlayOpen: boolean =  false;

    @action openNavigationOverlay() {
        this.navigationOverlayOpen = true;
    }

    @action closeNavigationOverlay() {
        this.navigationOverlayOpen = false;
    }

    handleNavigationButtonClick = () => {
        this.openNavigationOverlay();
    };

    handleNavigationOverlayClose = () => {
        this.closeNavigationOverlay();
    };

    render() {
        const {router} = this.props;

        return (
            <div>
                <Toolbar onNavigationButtonClick={this.handleNavigationButtonClick} />
                <main className={applicationStyles.main}>
                    {router.route &&
                        <ViewRenderer
                            key={router.route.name}
                            name={router.route.view}
                            router={router}
                        />
                    }
                </main>
                <SplitView />
                <Overlay
                    title="Navigation"
                    onClose={this.handleNavigationOverlayClose}
                    confirmText="Close"
                    onConfirm={this.handleNavigationOverlayClose}
                    open={this.navigationOverlayOpen}
                >
                    <ul
                        style={{
                            listStyle: 'none',
                            display: 'flex',
                            alignItems: 'left',
                            justifyContent: 'center',
                            flexDirection: 'column',
                        }}
                    >
                        {routes.map((route, index) => {
                            const handleClick = () => {
                                router.navigate(route.key);
                                this.closeNavigationOverlay();
                            };

                            return (
                                <li
                                    key={index}
                                    onClick={handleClick}
                                    style={{
                                        padding: 10,
                                        color: '#52b6ca',
                                        cursor: 'pointer',
                                    }}
                                >
                                    {route.name}
                                </li>
                            );
                        })}
                    </ul>
                </Overlay>
            </div>
        );
    }
}
