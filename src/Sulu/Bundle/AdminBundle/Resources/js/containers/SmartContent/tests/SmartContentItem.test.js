// @flow
import React from 'react';
import {render} from '@testing-library/react';
import SmartContentItem from '../SmartContentItem';

test('Render item with only title', () => {
    const item = {title: 'Only title'};
    const {asFragment} = render(<SmartContentItem item={item} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Render item with title and draft with published state', () => {
    const item = {title: 'Draft and published', publishedState: false, published: new Date()};
    const {asFragment} = render(<SmartContentItem item={item} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Render item with title and published state', () => {
    const item = {title: 'Published', publishedState: true, published: new Date()};
    const {asFragment} = render(<SmartContentItem item={item} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Render item with title and image', () => {
    const item = {image: 'image.jpg', title: 'Image'};
    const {asFragment} = render(<SmartContentItem item={item} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Render item with additional columns except for id', () => {
    const item = {id: 4, title: 'Title with URL', url: '/url', value: 'Test'};
    const {asFragment} = render(<SmartContentItem item={item} />);
    expect(asFragment()).toMatchSnapshot();
});
