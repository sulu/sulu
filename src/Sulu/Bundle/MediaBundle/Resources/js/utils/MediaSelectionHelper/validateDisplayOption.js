// @flow

export default function validateDisplayOption(name: ?string | number): boolean %checks {
    return name === 'leftTop'
        || name === 'top'
        || name === 'rightTop'
        || name === 'left'
        || name === 'middle'
        || name === 'right'
        || name === 'leftBottom'
        || name === 'bottom'
        || name === 'rightBottom';
}
