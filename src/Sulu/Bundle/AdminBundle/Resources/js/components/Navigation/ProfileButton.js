// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import Icon from '../Icon';
import Button from '../Button';
import {translate} from '../../utils';
import profileButtonStyles from './profileButton.scss';

type Props = {
    onLogoutClick: () => void,
    onProfileClick: () => void,
    suluVersion: string,
    suluVersionLink: string,
    userImage: ?string,
    username: string,
}

@observer
class ProfileButton extends React.Component<Props> {
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

    handleSuluClick = () => {
        const {suluVersionLink} = this.props;

        this.close();
        window.open(suluVersionLink, '_blank').focus();
    };

    render() {
        const {username, userImage, suluVersion} = this.props;

        const menuClass = classNames(profileButtonStyles.menu, this.open && profileButtonStyles.open);

        return (
            <div className={profileButtonStyles.profileButton}>
                <div
                    className={profileButtonStyles.button}
                    onClick={this.handleButtonClick}
                    role="button"
                >
                    <div className={profileButtonStyles.userImage}>
                        {userImage && (
                            <img
                                alt={username}
                                className={profileButtonStyles.image}
                                src={userImage}
                                title={username}
                            />)
                        }

                        {!userImage && <Icon className={profileButtonStyles.placeholder} name="su-user" />}
                    </div>

                    <span className={profileButtonStyles.username}>
                        {username}
                    </span>

                    <Icon name={this.open ? 'su-angle-down' : 'su-angle-up'} />
                </div>

                <div className={menuClass}>
                    <Button
                        className={profileButtonStyles.menuButton}
                        icon="su-user"
                        onClick={this.handleProfileClick}
                        size="large"
                        skin="text"
                    >
                        {translate('sulu_admin.edit_profile')}
                    </Button>

                    <Button
                        className={profileButtonStyles.menuButton}
                        icon="su-sulu"
                        onClick={this.handleSuluClick}
                        size="large"
                        skin="text"
                    >
                        Sulu ({suluVersion})
                    </Button>

                    <Button
                        className={profileButtonStyles.menuButton}
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

export default ProfileButton;
