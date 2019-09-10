// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import Icon from '../Icon';
import snackbarStyles from './snackbar.scss';

type Props = {|
    message: string,
    onClick?: () => void,
    onCloseClick?: () => void,
    type: 'error' | 'warning',
    visible: boolean,
|};

const ICONS = {
    error: 'su-exclamation-triangle',
    warning: 'su-bell',
};

@observer
class Snackbar extends React.Component<Props> {
    static defaultProps = {
        visible: true,
    };

    @observable message: ?string;

    @action updateMessage = () => {
        this.message = this.props.message;
    };

    componentDidMount() {
        this.updateMessage();
    }

    componentDidUpdate(prevProps: Props) {
        const {message, visible} = this.props;

        if (prevProps.message !== message && visible) {
            this.updateMessage();
        }
    }

    @action handleTransitionEnd = () => {
        const {visible} = this.props;

        if (!visible) {
            this.message = undefined;
        }
    };

    render() {
        const {onCloseClick, onClick, type, visible} = this.props;

        const snackbarClass = classNames(
            snackbarStyles.snackbar,
            snackbarStyles[type],
            {
                [snackbarStyles.clickable]: onClick,
                [snackbarStyles.visible]: visible,
            }
        );

        return (
            <div className={snackbarClass} onClick={onClick} onTransitionEnd={this.handleTransitionEnd} role="button">
                <Icon className={snackbarStyles.icon} name={ICONS[type]} />
                <div className={snackbarStyles.text}>
                    <strong>{translate('sulu_admin.' + type)}</strong> - {this.message}
                </div>
                {onCloseClick &&
                    <Icon className={snackbarStyles.closeIcon} name="su-times" onClick={onCloseClick} />
                }
            </div>
        );
    }
}

export default Snackbar;
