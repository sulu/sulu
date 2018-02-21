// @flow
import React from 'react';
import croppedTextStyle from './croppedText.scss';

type Props = {
    children: ?(string | number),
};

export default class CroppedText extends React.PureComponent<Props> {
    render() {
        let {children} = this.props;

        if (!children) {
            return null;
        }

        children = children.toString();

        const index = Math.ceil(children.length / 2);
        const frontText = children.substr(0, index);
        const backText = children.substr(index);

        return (
            <div
                title={children}
                aria-label={children}
                className={croppedTextStyle.croppedText}
            >
                <div className={croppedTextStyle.front} aria-hidden={true}>{frontText}</div>
                <div className={croppedTextStyle.back} aria-hidden={true}><span>{backText}</span></div>
                <div className={croppedTextStyle.whole}>{children}</div>
            </div>
        );
    }
}
