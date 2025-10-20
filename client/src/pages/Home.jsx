import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, CheckCircle, Code, PenTool, Settings, Cloud, Phone, Mail, MapPin} from 'lucide-react';
import { motion } from 'framer-motion';

// Animation Variants
const fadeInUp = {
  initial: { opacity: 0, y: 50 },
  whileInView: { opacity: 1, y: 0 },
  transition: { duration: 0.6, ease: 'easeOut' }
};

const staggerContainer = {
  hidden: {},
  show: {
    transition: {
      staggerChildren: 0.2
    }
  }
};

const fadeIn = (delay = 0) => ({
  initial: { opacity: 0, y: 20 },
  whileInView: { opacity: 1, y: 0 },
  transition: { duration: 0.6, delay }
});

const Home = () => {
  const stats = [
    { value: '20+', label: 'Years in Medical Industry' },
    { value: '400+', label: 'Hospitals & Clinics Served' },
    { value: '2000+', label: 'Medical Equipment Delivered' },
    { value: '10+', label: 'Skilled Professionals' }
  ];

  const serviceItems = [
    {
      id: 'software',
      icon: <Code size={40} className="text-[#1d7d69]" />,
      title: "Medical Equipment Supply",
      description: "We offer a broad selection of advanced medical equipment for hospitals.",
      link: '/services#software'
    },
    {
      id: 'design',
      icon: <PenTool size={40} className="text-[#1d7d69]" />,
      title: "Installation & Maintenance",
      description: "Our trained technicians ensure proper installation and provide ongoing maintenance.",
      link: '/services#design'
    },
    {
      id: 'consulting',
      icon: <Settings size={40} className="text-[#1d7d69]" />,
      title: "Consultation & Procurement",
      description: "We help healthcare providers make informed decisions with expert consultation.",
      link: '/services#consulting'
    },
    {
      id: 'cloud',
      icon: <Cloud size={40} className="text-[#1d7d69]" />,
      title: "After-Sales Support",
      description: "We provide reliable after-sales service, including training and technical assistance.",
      link: '/services#cloud'
    }
  ];

  return (
    <>
      {/* Hero Section - Mobile Optimized */}
      <motion.div className="relative bg-gradient-to-r from-[#1d7d69] to-[#21b095] text-white overflow-hidden" {...fadeInUp}>
        <div className="absolute inset-0 opacity-10">
          <div className="absolute right-0 bottom-0 w-1/2 h-1/2 bg-white rounded-full transform translate-x-1/2 translate-y-1/2"></div>
          <div className="absolute left-0 top-0 w-1/3 h-1/3 bg-white rounded-full transform -translate-x-1/2 -translate-y-1/2"></div>
        </div>

        <div className="container mx-auto relative px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10 items-center min-h-[480px] sm:min-h-[520px] md:min-h-[600px] lg:min-h-[630px] py-10 sm:py-12 md:py-16 lg:py-20">
            <motion.div className="space-y-5 md:space-y-6 text-left" {...fadeIn(0.2)}>
              <h1 className="text-5xl sm:text-8xl md:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight">
                Medical Equipment Solutions to Power Modern Healthcare 
              </h1>
              <p className="text-base sm:text-lg md:text-xl opacity-90 max-w-lg">
                We deliver cutting-edge reliable medical equipment for today's healthcare needs.
              </p>
              <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-2">
                <Link to="/contact" className="bg-white text-[#1d7d69] hover:bg-gray-100 font-semibold py-3.5 px-8 rounded-lg transition-all text-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                  Get Started
                </Link>
                <Link to="/store" className="flex items-center justify-center text-white border-2 border-white bg-transparent hover:bg-white/10 font-semibold py-3.5 px-8 rounded-lg transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                  Our Store <ArrowRight size={18} className="ml-2" />
                </Link>
              </div>
            </motion.div>

            <motion.div className="flex justify-center lg:justify-end mt-6 md:mt-0" {...fadeIn(0.4)}>
              <img 
                src="/download.jpeg" 
                alt="Technology Solutions" 
                className="rounded-xl shadow-2xl max-w-full h-auto"
                style={{ maxHeight: '320px' }} 
              />
            </motion.div>
          </div>
        </div>
      </motion.div>

      {/* About Section - Mobile Enhanced */}
      <motion.section id="about" className="py-12 sm:py-16 md:py-20 lg:py-24 bg-white" {...fadeInUp}>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">
            <motion.div className="relative" {...fadeIn(0.2)}>
              <div className="rounded-xl overflow-hidden shadow-2xl">
                <img src="/hero1.jpeg" alt="About Sreemeditec" className="w-full h-auto object-cover" />
              </div>
              
              {/* Mobile Stats Card */}
              <div className="mt-6 lg:hidden bg-[#1d7d69] text-white rounded-xl p-6 shadow-xl">
                <div className="grid grid-cols-2 gap-4">
                  {stats.map((stat, index) => (
                    <div key={index} className="text-center">
                      <div className="text-2xl sm:text-3xl font-bold">{stat.value}</div>
                      <div className="text-xs sm:text-sm opacity-90 mt-1">{stat.label}</div>
                    </div>
                  ))}
                </div>
              </div>
              
              {/* Desktop Stats Card */}
              <div className="hidden lg:flex absolute -bottom-6 -right-6 bg-[#1d7d69] text-white rounded-xl p-6 shadow-xl">
                <div className="grid grid-cols-2 gap-6">
                  {stats.map((stat, index) => (
                    <div key={index} className="text-center">
                      <div className="text-2xl font-bold">{stat.value}</div>
                      <div className="text-sm opacity-80">{stat.label}</div>
                    </div>
                  ))}
                </div>
              </div>
            </motion.div>

            <motion.div className="space-y-5 md:space-y-6" {...fadeIn(0.3)}>
              <span className="inline-block bg-[#e0f2f1] text-[#1d7d69] text-sm font-medium px-4 py-1.5 rounded-full">
                About Sreemeditec
              </span>
              <h2 className="text-2xl sm:text-3xl md:text-4xl leading-snug font-bold text-gray-900">
                Your Trusted Partner for Technology Solutions Since 2005
              </h2>
              <p className="text-gray-600 text-base sm:text-lg leading-relaxed">
                <strong className="text-gray-800 font-medium">Delivering Trust. Powering Care.</strong><br />
                For over 20 years, Sreemeditec has been at the heart of healthcare...
              </p>
              <div className="space-y-3">
                {[
                  'Trusted supplier of certified medical technologies',
                  'Dedicated after-sales service & technical support',
                  'Scalable solutions for clinics to multi-specialty hospitals'
                ].map((point, i) => (
                  <div key={i} className="flex items-start">
                    <CheckCircle size={20} className="text-[#1d7d69] mt-1 mr-3 flex-shrink-0" />
                    <p className="text-gray-700 text-sm sm:text-base">{point}</p>
                  </div>
                ))}
              </div>
              <div className="pt-4 text-center md:text-left">
                <Link to="/about" className="inline-flex items-center bg-[#1d7d69] text-white px-6 py-3 rounded-lg hover:bg-[#166353] transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                  About Us <ArrowRight size={18} className="ml-2" />
                </Link>
              </div>
            </motion.div>
          </div>
        </div>
      </motion.section>

      {/* Services Section - Mobile Optimized */}
      <motion.section id="services" className="py-12 sm:py-16 md:py-20 lg:py-24 bg-[#f9fafb]" {...fadeInUp}>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div className="text-center max-w-3xl mx-auto mb-12 md:mb-16" {...fadeIn(0.2)}>
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800 mb-4">Our Services</h2>
            <p className="text-gray-600 text-base sm:text-lg leading-relaxed">
              We offer a range of high-quality medical equipment and services to help healthcare professionals...
            </p>
          </motion.div>

          <motion.div variants={staggerContainer} initial="hidden" whileInView="show" viewport={{ once: true }} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
            {serviceItems.map((service, i) => (
              <motion.div key={service.id} variants={fadeIn(i * 0.1)} className="bg-white rounded-2xl shadow-md hover:shadow-2xl transition-all duration-300 p-6 group transform hover:-translate-y-1">
                <div className="mb-5 transition-transform duration-300 group-hover:scale-110">
                  {service.icon}
                </div>
                <h3 className="text-lg sm:text-xl font-semibold text-gray-800 mb-2">{service.title}</h3>
                <p className="text-sm sm:text-base text-gray-600 mb-4 leading-relaxed">{service.description}</p>
                <Link to={service.link} className="inline-flex items-center text-[#1d7d69] hover:underline font-semibold text-sm">
                  Learn more <ArrowRight size={16} className="ml-1" />
                </Link>
              </motion.div>
            ))}
          </motion.div>

          <motion.div className="mt-12 md:mt-16 text-center" {...fadeIn(0.4)}>
            <Link to="/services" className="inline-flex items-center bg-[#1d7d69] text-white text-base font-medium px-8 py-3.5 rounded-lg hover:bg-[#166353] transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
              View All Services <ArrowRight size={18} className="ml-2" />
            </Link>
          </motion.div>
        </div>
      </motion.section>

      {/* Contact Section - Mobile Enhanced */}
      <motion.section id="contact" className="py-12 sm:py-16 md:py-20 lg:py-24 bg-[#f9fafb]" {...fadeInUp}>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div className="text-center max-w-3xl mx-auto mb-12 md:mb-16" {...fadeIn(0.2)}>
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-800 mb-4">Get In Touch</h2>
            <p className="text-base sm:text-lg text-gray-600 leading-relaxed">
              Looking for quality medical equipment? Contact us for a free consultation today!
            </p>
          </motion.div>

          <motion.div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8" variants={staggerContainer} initial="hidden" whileInView="show" viewport={{ once: true }}>
            {[{
              icon: <Phone size={28} />,
              title: "Call Us",
              desc: "Our team is here to help during business hours.",
              link: "tel:+919884818398",
              text: "+91 98848 18398"
            }, {
              icon: <Mail size={28} />,
              title: "Email Us",
              desc: "Get in touch with our support team.",
              link: "mailto:sreemeditec@gmail.com",
              text: "sreemeditec@gmail.com"
            }, {
              icon: <MapPin size={28} />,
              title: "Visit Us",
              desc: "Come by our office.",
              address: `No:18/2, Bajanai Koil Street,\nRajakilpakkam, Chennai-73,\nTamil Nadu, India`
            }].map((item, i) => (
              <motion.div key={i} className="bg-white rounded-xl shadow-md hover:shadow-2xl p-6 text-center transition-all duration-300 transform hover:-translate-y-1 group" variants={fadeIn(i * 0.1)}>
                <div className="w-16 h-16 mx-auto flex items-center justify-center bg-[#e0f2f1] text-[#1d7d69] rounded-full mb-4 transition-transform duration-300 group-hover:scale-110">
                  {item.icon}
                </div>
                <h3 className="text-xl font-semibold text-gray-800 mb-2">{item.title}</h3>
                <p className="text-gray-600 mb-4 text-sm sm:text-base">{item.desc}</p>
                {item.link ? (
                  <a href={item.link} className="text-[#1d7d69] font-medium hover:underline text-sm sm:text-base break-words">
                    {item.text}
                  </a>
                ) : (
                  <address className="not-italic text-[#1d7d69] font-medium text-sm sm:text-base leading-relaxed whitespace-pre-line">
                    {item.address}
                  </address>
                )}
              </motion.div>
            ))}
          </motion.div>

          <motion.div className="mt-12 md:mt-16 text-center" {...fadeIn(0.3)}>
            <Link to="/contact" className="inline-flex items-center bg-[#1d7d69] text-white px-8 py-3.5 rounded-lg hover:bg-[#166353] transition-all text-base font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
              Contact <ArrowRight size={18} className="ml-2" />
            </Link>
          </motion.div>
        </div>
      </motion.section>
      {/* CTA */}
      <section className="py-20 bg-[#1d7d69] text-white text-center">
        <div className="max-w-xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-bold mb-4">Ready to Start Your Hospital Project?</h2>
          <p className="text-lg mb-6">Contact us for a free consultation. Let’s bring your vision to life.</p>
          <Link to="/contact">
            <button className="bg-white text-[#1d7d69] font-semibold py-3 px-6 rounded hover:bg-gray-100 transition">
              Contact Us <ArrowRight className="inline ml-2 w-5 h-5" />
            </button>
          </Link>
        </div>
      </section>
    </>
  );
};

export default Home;
