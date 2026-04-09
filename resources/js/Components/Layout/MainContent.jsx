import Dashboard from '../Dashboard';

/**
 * MainContent Component - Dashboard Content Container
 * 
 * Props:
 *  - children?: ReactNode - Konten dashboard utama
 *  - sections?: Object - Region-specific content (prompt, sqlReview, schema, etc)
 */

export default function MainContent({ children, sections, dashboardProps }) {
  return (
    <main>
      {children || <Dashboard {...dashboardProps} />}
    </main>
  );
}
