// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import Icon from '../Icon';
import Item from './Item';
import navigationStyles from './navigation.scss';
import UserSection from './UserSection';
import type {ChildrenArray, Element} from 'react';

type Props = {|
    appVersion: ?string,
    appVersionLink?: string,
    children: ChildrenArray<Element<typeof Item>>,
    onItemClick: (id: string) => void,
    onLogoutClick: () => void,
    onPinToggle?: () => void,
    onProfileClick: () => void,
    pinned?: boolean,
    suluVersion: string,
    suluVersionLink: string,
    title: string,
    userImage: ?string,
    username: string,
|};

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

    constructor(props: Props) {
        super(props);

        this.findDefaultExpandedChild(this.props.children);
    }

    componentDidUpdate(prevProps: Props) {
        if (prevProps.children !== this.props.children) {
            this.findDefaultExpandedChild(this.props.children);
        }
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

    handleItemClick = (value: *) => {
        if (this.expandedChild === value) {
            this.setExpandedChild(null);

            return;
        }

        this.setExpandedChild(value);
        this.props.onItemClick(value);
    };

    cloneChildren(): ChildrenArray<Element<typeof Item>> {
        return React.Children.map(this.props.children, (child) => {
            return React.cloneElement(child, {
                children: child.props.children ? React.Children.map(child.props.children, (subChild) => {
                    if (!subChild) {
                        return;
                    }

                    return React.cloneElement(subChild, {
                        onClick: this.handleItemClick,
                    });
                }) : undefined,
                expanded: child.props.value === this.expandedChild
                    || (
                        child.props.children
                        && child.props.children.some((child) => child.props.value === this.expandedChild)
                    ),
                onClick: this.handleItemClick,
            });
        });
    }

    handlePinToggle = () => {
        const {onPinToggle} = this.props;

        if (onPinToggle) {
            onPinToggle();
        }
    };

    render() {
        const {
            pinned,
            username,
            userImage,
            onLogoutClick,
            onProfileClick,
            suluVersion,
            onPinToggle,
        } = this.props;

        const pinClass = classNames(navigationStyles.pin, {[navigationStyles.active]: pinned});

        return (
            <div className={navigationStyles.navigation}>
                <div className={navigationStyles.header}>
                    <span className={navigationStyles.logo} title={suluVersion}>
                        <Icon name="su-sulu-logo" />
                    </span>

                    {onPinToggle &&
                        <div className={pinClass} onClick={this.handlePinToggle} role="button">
                            <Icon className={navigationStyles.pinIcon} name="su-stick-right" />
                        </div>
                    }
                </div>

                <div className={navigationStyles.items}>
                    {this.cloneChildren()}
                </div>

                <div className={navigationStyles.footer}>
                    <UserSection
                        onLogoutClick={onLogoutClick}
                        onProfileClick={onProfileClick}
                        userImage={userImage}
                        username={username}
                    />
                </div>
            </div>
        );
    }
}

export default Navigation;
