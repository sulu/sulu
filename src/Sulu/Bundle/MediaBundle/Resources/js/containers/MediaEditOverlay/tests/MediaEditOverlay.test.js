// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import FormOverlay from 'sulu-admin-bundle/containers/FormOverlay';
import MediaEditOverlay from '../MediaEditOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    mockExtendObservable(this, {dirty: false});
}));

jest.mock('sulu-admin-bundle/containers/FormOverlay', () => jest.fn(function(props) {
    return require('react').createElement('div', {}, props.title);
}));

beforeEach(() => {
    (ResourceStore: any).mockClear();
    (ResourceFormStore: any).mockClear();
    (FormOverlay: any).mockClear();
});

test('Render nothing if the overlay is closed', () => {
    render(
        <MediaEditOverlay
            id={5}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
        />
    );

    expect(screen.queryByText('sulu_media.edit_media')).not.toBeInTheDocument();
    expect(ResourceStore).not.toHaveBeenCalled();
});

test('Create a form store for the media details form of the given media', () => {
    const locale = observable.box('de');
    const closeSpy = jest.fn();
    const confirmSpy = jest.fn();

    render(
        <MediaEditOverlay
            id={5}
            locale={locale}
            onClose={closeSpy}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    expect(screen.getByText('sulu_media.edit_media')).toBeInTheDocument();
    expect(ResourceStore).toHaveBeenCalledWith('media', 5, {locale});
    expect(ResourceFormStore).toHaveBeenCalledWith(expect.any(ResourceStore), 'media_details', {}, undefined);

    const formOverlayProps = (FormOverlay: any).mock.calls[0][0];
    expect(formOverlayProps.formStore).toBe((ResourceFormStore: any).mock.instances[0]);
    expect(formOverlayProps.confirmDisabled).toEqual(true);
    expect(formOverlayProps.onClose).toBe(closeSpy);
    expect(formOverlayProps.onConfirm).toBe(confirmSpy);
    expect(formOverlayProps.open).toEqual(true);
});

test('Recreate the form store if another media is edited and destroy it when closed', () => {
    const locale = observable.box('en');

    const {rerender} = render(
        <MediaEditOverlay id={5} locale={locale} onClose={jest.fn()} onConfirm={jest.fn()} open={true} />
    );

    const firstFormStore = (ResourceFormStore: any).mock.instances[0];

    rerender(<MediaEditOverlay id={6} locale={locale} onClose={jest.fn()} onConfirm={jest.fn()} open={true} />);

    expect(firstFormStore.destroy).toHaveBeenCalled();
    expect(ResourceStore).toHaveBeenLastCalledWith('media', 6, {locale});

    const secondFormStore = (ResourceFormStore: any).mock.instances[1];

    rerender(<MediaEditOverlay id={6} locale={locale} onClose={jest.fn()} onConfirm={jest.fn()} open={false} />);

    expect(secondFormStore.destroy).toHaveBeenCalled();
    expect(screen.queryByText('sulu_media.edit_media')).not.toBeInTheDocument();
});
