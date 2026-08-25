// @flow
import React from 'react';

/**
 * @internal
 *
 * The shared icon font has no bent arrow, so it is drawn inline like the other icons of the AI features.
 */
export default class ReplyIcon extends React.PureComponent<{||}> {
    render() {
        return (
            <svg
                fill="none"
                height="16"
                viewBox="0 0 16 16"
                width="16"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M1.5 1.5V8.5C1.5 9.60457 2.39543 10.5 3.5 10.5H14"
                    stroke="currentColor"
                    strokeWidth="1.3"
                />
                <path
                    d="M10.5 7L14 10.5L10.5 14"
                    stroke="currentColor"
                    strokeLinecap="square"
                    strokeWidth="1.3"
                />
            </svg>
        );
    }
}
