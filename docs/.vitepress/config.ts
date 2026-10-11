import { defineConfig } from 'vitepress'

const guide = [
  { text: 'Installation', link: '/guide/installation' },
  { text: 'Getting started', link: '/guide/getting-started' },
  { text: 'Task types and payloads', link: '/guide/tasks' },
  { text: 'Frequencies and time windows', link: '/guide/frequencies' },
  { text: 'Running the scheduler', link: '/guide/running' },
  { text: 'Manual runs', link: '/guide/manual-runs' },
  { text: 'Configuration', link: '/guide/configuration' },
  { text: 'Security', link: '/guide/security' },
  { text: 'Upgrading', link: '/guide/upgrading' },
  { text: 'Contributing', link: '/guide/contributing' }
]

export default defineConfig({
  title: 'CakePHP QueueScheduler',
  description: 'Database schedules and recurring Queue jobs for CakePHP.',
  base: '/cakephp-queue-scheduler/',
  cleanUrls: true,
  srcExclude: ['README.md'],
  head: [['link', { rel: 'icon', href: '/cakephp-queue-scheduler/favicon.svg', type: 'image/svg+xml' }]],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Installation', link: '/guide/installation' },
      { text: 'Getting started', link: '/guide/getting-started' },
      { text: 'Reference', link: '/guide/tasks' },
    ],
    sidebar: [{ text: 'QueueScheduler plugin', items: guide }],
    search: { provider: 'local' },
    socialLinks: [{ icon: 'github', link: 'https://github.com/dereuromark/cakephp-queue-scheduler' }],
    editLink: { pattern: 'https://github.com/dereuromark/cakephp-queue-scheduler/edit/master/docs/:path' },
    footer: { message: 'Released under the MIT License.', copyright: 'Copyright Mark Scherer' },
  },
})
