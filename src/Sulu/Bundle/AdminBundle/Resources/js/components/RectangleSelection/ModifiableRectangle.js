// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import type {RectangleChange} from './types';
import modifiableRectangleStyles from './modifiableRectangle.scss';

type Props = {
    backdropSize: number,
    disabled: boolean,
    height: number,
    label: string | typeof undefined,
    left: number,
    minSizeReached: boolean,
    onChange?: (r: RectangleChange) => void,
    onDoubleClick?: () => void,
    onFinish?: () => void,
    top: number,
    width: number,
};

@observer
class ModifiableRectangle extends React.Component<Props> {
    static defaultProps = {
        backdropSize: 0,
        left: 0,
        top: 0,
    };

    @observable moveMode = false;
    @observable resizeMode = false;
    @observable clickAnchor = {pageY: 0, pageX: 0};

    componentDidMount() {
        window.addEventListener('mouseup', this.handleMouseUp);
        window.addEventListener('mousemove', this.handleMouseMove);
    }

    componentWillUnmount() {
        window.removeEventListener('mouseup', this.handleMouseUp);
        window.removeEventListener('mousemove', this.handleMouseMove);
    }

    @action setClickAnchor(event: MouseEvent) {
        this.clickAnchor.pageY = event.pageY;
        this.clickAnchor.pageX = event.pageX;
    }

    @action handleMoveMouseDown = (event: MouseEvent) => {
        event.stopPropagation();
        this.setClickAnchor(event);
        this.moveMode = true;
    };

    @action handleResizeMouseDown = (event: MouseEvent) => {
        event.stopPropagation();
        this.setClickAnchor(event);
        this.resizeMode = true;
    };

    @action handleMouseUp = () => {
        const {onFinish} = this.props;

        if (this.moveMode || this.resizeMode) {
            this.moveMode = false;
            this.resizeMode = false;

            if (onFinish) {
                onFinish();
            }
        }
    };

    @action handleMouseMove = (event: MouseEvent) => {
        const {onChange} = this.props;
        let top = 0, left = 0, width = 0, height = 0;

        if (this.moveMode) {
            top = event.pageY - this.clickAnchor.pageY;
            left = event.pageX - this.clickAnchor.pageX;
        }

        if (this.resizeMode) {
            height = event.pageY - this.clickAnchor.pageY;
            width = event.pageX - this.clickAnchor.pageX;
        }

        if (this.moveMode || this.resizeMode) {
            this.setClickAnchor(event);

            if (onChange) {
                onChange({top, left, width, height});
            }
        }
    };

    handleDoubleClick = this.props.onDoubleClick;

    render() {
        const {backdropSize, disabled, height, label, left, minSizeReached, top, width} = this.props;

        const rectangleClass = classNames(
            modifiableRectangleStyles.rectangle,
            {
                [modifiableRectangleStyles.disabled]: disabled,
                [modifiableRectangleStyles.hasBackdrop]: !!backdropSize,
            }
        );

        return (
            <Fragment>
                <div
                    className={rectangleClass}
                    onDoubleClick={!disabled && this.handleDoubleClick || undefined}
                    onMouseDown={!disabled && this.handleMoveMouseDown || undefined}
                    role="button"
                    style={{
                        left: left + 'px',
                        top: top + 'px',
                        width: width + 'px',
                        height: height + 'px',
                    }}
                >
                    {!!backdropSize &&
                        <div
                            className={modifiableRectangleStyles.backdrop}
                            style={{outlineWidth: backdropSize + 'px'}}
                        />
                    }
                    {!!label &&
                        <div
                            className={modifiableRectangleStyles.label}
                            style={{fontSize: `${Math.sqrt(height / 2) * 5}px`}}
                        >
                            {label}
                        </div>
                    }
                    {!disabled &&
                        <div
                            className={modifiableRectangleStyles.resizeHandle}
                            onMouseDown={this.handleResizeMouseDown}
                            role="slider"
                        />
                    }
                </div>
                {minSizeReached &&
                    <div
                        className={modifiableRectangleStyles.minSizeNotification}
                        style={{
                            left: left + 'px',
                            top: top + height + 'px',
                            width: width + 'px',
                        }}
                    >
                        {translate('sulu_media.min_size_notification')}
                    </div>
                }
            </Fragment>
        );
    }
}

export default ModifiableRectangle;
