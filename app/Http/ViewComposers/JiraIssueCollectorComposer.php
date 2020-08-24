<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;

class JiraIssueCollectorComposer
{
    /**
     * @param View $view
     * @return void
     */
    public function compose(View $view)
    {
        $view->with('issueCollector', $this->getIssueCollector());
    }

    /**
     * @return string|boolean
     */
    private function getIssueCollector()
    {
        return app()->environment() !== 'production' && config('config.jira_issue_collector.enabled')
            ? config('config.jira_issue_collector.cdn_url')
            : false;
    }
}
