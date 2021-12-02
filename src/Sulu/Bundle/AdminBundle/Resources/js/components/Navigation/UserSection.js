// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import Icon from '../Icon';
import Button from '../Button';
import {translate} from '../../utils';
import userSectionStyles from './userSection.scss';

type Props = {
    onLogoutClick: () => void,
    onProfileClick: () => void,
    userImage: ?string,
    username: string,
}

@observer
class UserSection extends React.Component<Props> {
    @observable open: boolean = false;

    @action handleButtonClick = () => {
        this.open = !this.open;
    };

    @action close = () => {
        this.open = false;
    };

    handleProfileClick = () => {
        const {onProfileClick} = this.props;

        this.close();
        onProfileClick();
    };

    handleLogoutClick = () => {
        const {onLogoutClick} = this.props;

        this.close();
        onLogoutClick();
    };

    render() {
        const {username, userImage} = this.props;

        const menuClass = classNames(userSectionStyles.menu, this.open && userSectionStyles.open);
        const buttonClass = classNames(userSectionStyles.button, this.open && userSectionStyles.active);

        return (
            <div className={userSectionStyles.userSection}>
                <div
                    className={buttonClass}
                    onClick={this.handleButtonClick}
                    role="button"
                >
                    <div className={userSectionStyles.userImage}>
                        {userImage && (
                            <img
                                alt={username}
                                className={userSectionStyles.image}
                                src={userImage}
                                title={username}
                            />)
                        }

                        {!userImage && <Icon className={userSectionStyles.placeholder} name="su-user" />}
                    </div>

                    <span className={userSectionStyles.username}>
                        {username}
                    </span>

                    <Icon name={this.open ? 'su-angle-down' : 'su-angle-up'} />
                </div>

                <div className={menuClass}>
                    <Button
                        className={userSectionStyles.menuButton}
                        icon="su-user"
                        onClick={this.handleProfileClick}
                        size="large"
                        skin="text"
                    >
                        {translate('sulu_admin.edit_profile')}
                    </Button>

                    <Button
                        className={userSectionStyles.menuButton}
                        icon="su-sign-out-alt"
                        onClick={this.handleLogoutClick}
                        size="large"
                        skin="text"
                    >
                        {translate('sulu_admin.logout')}
                    </Button>
                </div>
            </div>
        );
    }
}

export default UserSection;
