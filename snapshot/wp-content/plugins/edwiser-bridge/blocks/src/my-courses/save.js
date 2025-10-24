import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
  return (
    <div {...useBlockProps.save()}>
      <div
        id="eb-my-courses"
        data-page-title={attributes.pageTitle || ''}
        data-recommended-courses-title={
          attributes.recommendedCoursesTitle || ''
        }
        data-recommended-courses-count={attributes.recommendedCoursesCount || 3}
        data-show-course-progress={attributes.showCourseProgress}
        data-show-recommended-courses={attributes.showRecommendedCourses}
        data-hide-page-title={attributes.hidePageTitle}
      ></div>
    </div>
  );
}
