// @flow
import React from 'react';
import dividerStyles from './divider.scss';

type Props = {
    children?: string,
    description?: string,
};

export default class Divider extends React.PureComponent<Props> {
    render() {
        const {children, description} = this.props;

        return (
            <>
                <div className={dividerStyles.divider}>
                    {children}
                </div>
                {description &&
                    <div className={dividerStyles.description}>
                        {description}
                    </div>
                }
            </>
        );
    }
}
