// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Icon from '../Icon';
import Button from '../Button';
import Item from './Item';
import navigationStyles from './navigation.scss';

type Props = {
    appVersion: ?string,
    appVersionLink?: string,
    children: ChildrenArray<Element<typeof Item>>,
    onLogoutClick: () => void,
    onPinToggle?: () => void,
    onProfileClick: () => void,
    pinned?: boolean,
    suluVersion: string,
    suluVersionLink: string,
    title: string,
    userImage: ?string,
    username: string,
};

@observer
class Navigation extends React.Component<Props> {
    static defaultProps = {
        appVersion: undefined,
        pinned: false,
        userImage: undefined,
    };

    static Item = Item;

    @observable expandedChild: * = null;

    @action setExpandedChild(value: *) {
        this.expandedChild = value;
    }

    componentWillReceiveProps(newProps: Props) {
        this.findDefaultExpandedChild(newProps.children);
    }

    constructor(props: Props) {
        super(props);

        this.findDefaultExpandedChild(this.props.children);
    }

    findDefaultExpandedChild = (children: ChildrenArray<Element<typeof Item>>) => {
        let newExpandedChild = null;
        React.Children.forEach(children, (child) => {
            if (child.props.children) {
                React.Children.forEach(child.props.children, (subChild) => {
                    if (subChild.props.active) {
                        newExpandedChild = child.props.value;
                    }
                });
            }
        });

        this.setExpandedChild(newExpandedChild);
    };

    handleExpand = (value: *) => {
        if (this.expandedChild === value) {
            this.setExpandedChild(null);

            return;
        }

        this.setExpandedChild(value);
    };

    cloneChildren(): ChildrenArray<Element<typeof Item>> {
        return React.Children.map(this.props.children, (child) => {
            return React.cloneElement(child, {
                onClick: child.props.children ? this.handleExpand : child.props.onClick,
                expanded: child.props.value === this.expandedChild,
            });
        });
    }

    renderUserImage() {
        const {userImage, username, onProfileClick} = this.props;

        if (userImage) {
            return (<img onClick={onProfileClick} src={userImage} title={username} />);
        }

        return (
            <div className={navigationStyles.noUserImage} onClick={onProfileClick}>
                <Icon name="fa-user" />
            </div>
        );
    }

    handlePinToggle = () => {
        const {onPinToggle} = this.props;

        if (onPinToggle) {
            onPinToggle();
        }
    };

    renderAppVersion() {
        const {
            title,
            appVersion,
            appVersionLink,
        } = this.props;

        if (!appVersion) {
            return null;
        }

        if (!appVersionLink) {
            return <div>{title} ({appVersion})</div>;
        }

        return <div>{title} (<a href={appVersionLink} rel="noopener noreferrer" target="_blank">{appVersion}</a>)</div>;
    }

    render() {
        const {
            pinned,
            title,
            username,
            onLogoutClick,
            onProfileClick,
            suluVersion,
            suluVersionLink,
            onPinToggle,
        } = this.props;

        return (
            <div className={navigationStyles.navigation}>
                <div className={navigationStyles.header}>
                    <div className={navigationStyles.headerContent}>
                        <Icon className={navigationStyles.headerIcon} name="su-sulu" />
                        <span className={navigationStyles.headerTitle}>{title}</span>
                    </div>
                </div>
                <div className={navigationStyles.user}>
                    <div className={navigationStyles.userContent}>
                        {this.renderUserImage()}
                        <div className={navigationStyles.userProfile}>
                            <span onClick={onProfileClick}>{username}</span>
                            <button onClick={onLogoutClick}><Icon name="su-exit" />Log out</button>
                        </div>
                    </div>
                </div>
                <div className={navigationStyles.items}>
                    {this.cloneChildren()}
                </div>
                <div className={navigationStyles.footer}>
                    {onPinToggle &&
                        <Button
                            active={pinned}
                            activeClassName={navigationStyles.pinActive}
                            className={navigationStyles.pin}
                            icon="fa-thumb-tack"
                            iconClassName={navigationStyles.pinIcon}
                            onClick={this.handlePinToggle}
                            skin="icon"
                        />
                    }
                    <div className={navigationStyles.versions}>
                        {this.renderAppVersion()}
                        <div>
                            Sulu (<a href={suluVersionLink} rel="noopener noreferrer" target="_blank">{suluVersion}</a>)
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

export default Navigation;
