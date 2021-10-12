// @flow
export type PreviewRouteName = 'start' | 'render' | 'update' | 'update-context' | 'stop' | 'preview-link';

export type PreviewMode = 'auto' | 'on_request' | 'off';

export type PreviewLink = {
    token: string,
};
