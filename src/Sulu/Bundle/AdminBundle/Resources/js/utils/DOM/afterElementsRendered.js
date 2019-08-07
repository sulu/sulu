// @flow
export default function afterElementsRendered(callback: () => *) {
    setTimeout(callback);
}
