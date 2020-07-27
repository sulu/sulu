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

    componentDidMount() {
        window.addEventListener('mouseup', this.handleMouseUp);
        window.addEventListener('mousemove', this.handleMouseMove);
    }

    componentWillUnmount() {
        window.removeEventListener('mouseup', this.handleMouseUp);
        window.removeEventListener('mousemove', this.handleMouseMove);
    }

    @action handleMoveMouseDown = (event: MouseEvent) => {
        event.stopPropagation();
        this.moveMode = true;
    };

    @action handleResizeMouseDown = (event: MouseEvent) => {
        event.stopPropagation();
        this.resizeMode = true;
    };

    @action handleMouseUp = () => {
        this.moveMode = false;
        this.resizeMode = false;
    };

    @action handleMouseMove = (event: MouseEvent) => {
        const {onChange} = this.props;

        if (!onChange) {
            return;
        }

        const {movementX, movementY} = event;
        let top = 0, left = 0, width = 0, height = 0;

        if (this.moveMode) {
            top = movementY;
            left = movementX;
        }

        if (this.resizeMode) {
            height = movementY;
            width = movementX;
        }

        if (this.moveMode || this.resizeMode) {
            onChange({top, left, width, height});
        }
    };

    handleDoubleClick = this.props.onDoubleClick;

    render() {
        const {height, left, minSizeReached, backdropSize, top, width, disabled, label} = this.props;

        const rectangleClass = classNames(
            modifiableRectangleStyles.rectangle,
            {
                [modifiableRectangleStyles.disabled]: disabled,
                [modifiableRectangleStyles.backdropDisabled]: !backdropSize,
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
