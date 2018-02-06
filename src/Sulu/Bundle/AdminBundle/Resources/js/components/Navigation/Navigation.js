// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Icon from '../Icon';
import Item from './Item';
import navigationStyles from './navigation.scss';

type Props = {
    children: ChildrenArray<Element<typeof Item>>,
    title: string,
    username: string,
    userImage?: string,
    onLogoutClick: () => void,
    onProfileClick: () => void,
    suluVersion: string,
    suluVersionLink: string,
    appVersion?: string,
    appVersionLink?: string,
};

@observer
export default class Navigation extends React.Component<Props> {
    static Item = Item;

    @observable expandedChild: * = null;

    @action setExpandedChild(value: *) {
        this.expandedChild = value;
    }

    componentWillReceiveProps(newProps: Props) {
        this.findDefaultExpandedChild(newProps.children);
    }

    componentWillMount() {
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
            return (<img onClick={onProfileClick} title={username} src={userImage} />);
        }

        return (
            <div onClick={onProfileClick} className={navigationStyles.noUserImage}>
                <Icon name="user" />
            </div>
        );
    }

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

        return <div>{title} (<a href={appVersionLink} target="_blank">{appVersion}</a>)</div>;
    }

    render() {
        const {
            title,
            username,
            onLogoutClick,
            onProfileClick,
            suluVersion,
            suluVersionLink,
        } = this.props;
        const userImage = this.renderUserImage();

        return (
            <div className={navigationStyles.navigation}>
                <div className={navigationStyles.header}>
                    <div className={navigationStyles.headerContent}>
                        {title}
                    </div>
                </div>
                <div className={navigationStyles.user}>
                    <div className={navigationStyles.userContent}>
                        {userImage}
                        <div className={navigationStyles.userProfile}>
                            <span onClick={onProfileClick}>{username}</span>
                            <button onClick={onLogoutClick}><Icon name="sign-out" />Log out</button>
                        </div>
                    </div>
                </div>
                <div className={navigationStyles.items}>
                    {this.cloneChildren()}
                </div>
                <div className={navigationStyles.versions}>
                    {this.renderAppVersion()}
                    <div>Sulu (<a href={suluVersionLink} target="_blank">{suluVersion}</a>)</div>
                </div>
            </div>
        );
    }
}
