// @flow
import type {ElementRef} from 'react';
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import type {SelectionData} from './types';
import modifiableCircleStyles from './modifiableCircle.scss';

type Props = {
    disabled: boolean,
    filled: boolean,
    label: string | typeof undefined,
    left: number,
    onChange?: (value: SelectionData) => void,
    onDoubleClick?: () => void,
    radius: number,
    resizable: boolean,
    top: number,
};

@observer
class ModifiableCircle extends React.Component<Props> {
    @observable moveMode = false;
    @observable resizeMode = false;
    @observable resizeAngle = 0;

    circleRef: ?ElementRef<'div'>;

    componentDidMount() {
        window.addEventListener('mouseup', this.handleMouseUp);
        window.addEventListener('mousemove', this.handleMouseMove);
    }

    componentWillUnmount() {
        window.removeEventListener('mouseup', this.handleMouseUp);
        window.removeEventListener('mousemove', this.handleMouseMove);
    }

    setCircleRef = (ref: ?ElementRef<'div'>) => {
        this.circleRef = ref;
    };

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
        const {onChange, radius, left, top} = this.props;

        if (this.moveMode) {
            const {movementX, movementY} = event;

            if (!onChange) {
                return;
            }

            onChange({
                radius,
                left: left + movementX,
                top: top + movementY,
            });
        }

        if (this.resizeMode) {
            if (!this.circleRef) {
                return;
            }

            const rect = this.circleRef.getBoundingClientRect();

            const circleX = rect.left + rect.width / 2;
            const circleY = rect.top + rect.height / 2;
            const {clientX: mouseX, clientY: mouseY} = event;

            const deltaX = mouseX - circleX;
            const deltaY = mouseY - circleY;

            this.resizeAngle = (Math.atan2(deltaY, deltaX) * 180) / Math.PI;

            if (!onChange) {
                return;
            }

            const newRadius = Math.sqrt(deltaX ** 2 + deltaY ** 2);

            onChange({
                left,
                top,
                radius: newRadius,
            });
        }
    };

    handleDoubleClick = this.props.onDoubleClick;

    render() {
        const {disabled, filled, resizable, label, radius, left, top} = this.props;
        const width = !resizable && radius === 0 ? 30 : radius * 2;
        const labelSize = radius === 0 ? 14 : Math.sqrt(radius) * 5;

        const circleClass = classNames(
            modifiableCircleStyles.circle,
            {
                [modifiableCircleStyles.disabled]: disabled,
                [modifiableCircleStyles.filled]: filled,
            }
        );

        return (
            <div
                className={circleClass}
                onDoubleClick={!disabled && this.handleDoubleClick || undefined}
                onMouseDown={!disabled && this.handleMoveMouseDown || undefined}
                ref={this.setCircleRef}
                role="button"
                style={{
                    left: left + 'px',
                    top: top + 'px',
                    width: width + 'px',
                    height: width + 'px',
                }}
            >
                {label &&
                    <div
                        className={modifiableCircleStyles.label}
                        style={{fontSize: `${labelSize}px`}}
                    >
                        {label}
                    </div>
                }
                {resizable && !disabled &&
                    <div
                        className={modifiableCircleStyles.resizeHandle}
                        onMouseDown={this.handleResizeMouseDown}
                        role="slider"
                        style={{
                            transformOrigin: `calc(50% + ${radius * -1}px) 50%`,
                            transform: `translate(calc(-50% + ${radius}px), -50%) rotate(${this.resizeAngle}deg)`,
                        }}
                    />
                }
            </div>
        );
    }
}

export default ModifiableCircle;
