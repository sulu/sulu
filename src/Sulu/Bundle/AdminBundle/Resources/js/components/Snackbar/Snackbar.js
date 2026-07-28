// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import Icon from '../Icon';
import snackbarStyles from './snackbar.scss';

export type SnackbarType = 'error' | 'warning' | 'info' | 'success';

type Props = {|
    action?: {|
        label: string,
        onClick: () => void,
    |},
    icon?: string,
    message: string,
    onClick?: () => void,
    onCloseClick?: () => void,
    skin: 'static' | 'floating',
    title?: string,
    type: SnackbarType,
    visible: boolean,
|};

const ICONS = {
    error: 'su-exclamation-triangle',
    warning: 'su-bell',
    info: 'su-exclamation-circle',
    success: 'su-check-circle',
};

const DEFAULT_SNACKBAR_TYPE: SnackbarType = 'error';

@observer
class Snackbar extends React.Component<Props> {
    static defaultProps = {
        skin: 'static',
        visible: true,
    };

    @observable message: ?string;
    @observable title: ?string;
    @observable type: SnackbarType = DEFAULT_SNACKBAR_TYPE;

    @action updateMessage = () => {
        this.message = this.props.message;
        this.title = this.props.title;
    };

    @action updateType = () => {
        this.type = this.props.type;
    };

    componentDidMount() {
        this.updateMessage();
        this.updateType();
    }

    componentDidUpdate(prevProps: Props) {
        const {message, type, visible} = this.props;

        if (!visible) {
            return;
        }

        if (prevProps.visible !== visible || prevProps.message !== message || prevProps.title !== this.props.title) {
            this.updateMessage();
        }

        if (prevProps.visible !== visible || prevProps.type !== type) {
            this.updateType();
        }
    }

    @action handleTransitionEnd = () => {
        const {visible} = this.props;

        if (!visible) {
            this.message = undefined;
            this.title = undefined;
            this.type = DEFAULT_SNACKBAR_TYPE;
        }
    };

    handleActionClick = (event: SyntheticEvent<HTMLButtonElement>) => {
        const {action: snackbarAction} = this.props;

        // the action is nested inside the clickable snackbar, so its own onClick must not be triggered as well
        event.stopPropagation();

        if (snackbarAction) {
            snackbarAction.onClick();
        }
    };

    render() {
        const {action: snackbarAction, icon, onCloseClick, onClick, skin, visible} = this.props;

        const snackbarClass = classNames(
            snackbarStyles.snackbar,
            snackbarStyles[this.type],
            {
                [snackbarStyles.clickable]: onClick,
                [snackbarStyles.floating]: skin === 'floating',
                [snackbarStyles.visible]: visible,
            }
        );

        return (
            <div className={snackbarClass} onClick={onClick} onTransitionEnd={this.handleTransitionEnd} role="button">
                <Icon className={snackbarStyles.icon} name={icon || ICONS[this.type]} />
                <div className={snackbarStyles.text}>
                    {
                        skin === 'static'
                            ? <>
                                <strong>{this.title ?? translate('sulu_admin.' + this.type)}</strong>{' - '}
                            </>
                            : null
                    }
                    {this.message}
                </div>
                {snackbarAction &&
                    <button className={snackbarStyles.action} onClick={this.handleActionClick} type="button">
                        {snackbarAction.label}
                    </button>
                }
                {onCloseClick &&
                    <Icon className={snackbarStyles.closeIcon} name="su-times" onClick={onCloseClick} />
                }
            </div>
        );
    }
}

export default Snackbar;
