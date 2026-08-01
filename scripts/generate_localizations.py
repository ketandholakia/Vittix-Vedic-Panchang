# -*- coding: utf-8 -*-
import os

langs = {
    'mr': {
        'lunarmonth': {
            'Chaitra': 'चैत्र', 'Vaishakha': 'वैशाख', 'Jyeshtha': 'ज्येष्ठ', 'Ashadha': 'आषाढ', 'Shravana': 'श्रावण', 'Bhadrapada': 'भाद्रपद', 'Ashwina': 'आश्विन', 'Kartika': 'कार्तिक', 'Margashirsha': 'मार्गशीर्ष', 'Pausha': 'पौष', 'Magha': 'माघ', 'Phalguna': 'फाल्गुन'
        },
        'nakshatra': {
            'Ashwini': 'अश्विनी', 'Bharani': 'भरणी', 'Krittika': 'कृत्तिका', 'Rohini': 'रोहिणी', 'Mrigashira': 'मृगशीर्ष', 'Ardra': 'आर्द्रा', 'Punarvasu': 'पुनर्वसू', 'Pushya': 'पुष्य', 'Ashlesha': 'आश्लेषा', 'Magha': 'मघा', 'PurvaPhalguni': 'पूर्वा फाल्गुनी', 'UttaraPhalguni': 'उत्तरा फाल्गुनी', 'Hasta': 'हस्त', 'Chitra': 'चित्रा', 'Swati': 'स्वाती', 'Vishakha': 'विशाखा', 'Anuradha': 'अनुराधा', 'Jyeshtha': 'ज्येष्ठा', 'Mula': 'मूळ', 'PurvaAshadha': 'पूर्वाषाढा', 'UttaraAshadha': 'उत्तराषाढा', 'Shravana': 'श्रवण', 'Dhanishtha': 'धनिष्ठा', 'Shatabhisha': 'शततारका', 'PurvaBhadrapada': 'पूर्वा भाद्रपदा', 'UttaraBhadrapada': 'उत्तरा भाद्रपदा', 'Revati': 'रेवती'
        },
        'paksha': {
            'Shukla': 'शुक्ल', 'Krishna': 'कृष्ण'
        },
        'tithi': {
            'ShuklaPratipada': 'प्रतिपदा', 'ShuklaD2': 'द्वितीया', 'ShuklaD3': 'तृतीया', 'ShuklaD4': 'चतुर्थी', 'ShuklaD5': 'पंचमी', 'ShuklaD6': 'षष्ठी', 'ShuklaD7': 'सप्तमी', 'ShuklaAshtami': 'अष्टमी', 'ShuklaD9': 'नवमी', 'ShuklaD10': 'दशमी', 'ShuklaD11': 'एकादशी', 'ShuklaD12': 'द्वादशी', 'ShuklaD13': 'त्रयोदशी', 'ShuklaChaturdashi': 'चतुर्दशी', 'Purnima': 'पौर्णिमा',
            'KrishnaPratipada': 'प्रतिपदा', 'KrishnaD2': 'द्वितीया', 'KrishnaD3': 'तृतीया', 'KrishnaD4': 'चतुर्थी', 'KrishnaD5': 'पंचमी', 'KrishnaD6': 'षष्ठी', 'KrishnaD7': 'सप्तमी', 'KrishnaAshtami': 'अष्टमी', 'KrishnaD9': 'नवमी', 'KrishnaD10': 'दशमी', 'KrishnaD11': 'एकादशी', 'KrishnaD12': 'द्वादशी', 'KrishnaD13': 'त्रयोदशी', 'KrishnaChaturdashi': 'चतुर्दशी', 'Amavasya': 'अमावास्या'
        },
        'vara': {
            'Ravivara': 'रविवार', 'Somavara': 'सोमवार', 'Mangalavara': 'मंगळवार', 'Budhavara': 'बुधवार', 'Guruvara': 'गुरुवार', 'Shukravara': 'शुक्रवार', 'Shanivara': 'शनिवार'
        },
        'yoga': {
            'Vishkumbha': 'विष्कंभ', 'Preeti': 'प्रीति', 'Ayushman': 'आयुष्मान', 'Saubhagya': 'सौभाग्य', 'Shobhana': 'शोभन', 'Atiganda': 'अतिगंड', 'Sukarman': 'सुकर्मा', 'Dhriti': 'धृति', 'Shoola': 'शूळ', 'Ganda': 'गंड', 'Vriddhi': 'वृद्धी', 'Dhruva': 'ध्रुव', 'Vyaghata': 'व्याघात', 'Harshana': 'हर्षण', 'Vajra': 'वज्र', 'Siddhi': 'सिद्धी', 'Vyatipata': 'व्यतीपात', 'Variyana': 'वरियान', 'Parigha': 'परिघ', 'Shiva': 'शिव', 'Siddha': 'सिद्ध', 'Sadhya': 'साध्य', 'Shubha': 'शुभ', 'Shukla': 'शुक्ल', 'Brahma': 'ब्रह्म', 'Indra': 'इंद्र', 'Vaidhriti': 'वैधृती'
        },
        'karana': {
            'Bava': 'बव', 'Balava': 'बालव', 'Kaulava': 'कौलव', 'Taitila': 'तैतिल', 'Garija': 'गर', 'Vanija': 'वणिज', 'Vishti': 'विष्टी', 'Kimstughna': 'किंस्तुघ्न', 'Shakuni': 'शकुनी', 'Chatushpada': 'चतुष्पाद', 'Naga': 'नाग'
        },
        'rashi': {
            'Mesha': 'मेष', 'Vrishabha': 'वृषभ', 'Mithuna': 'मिथुन', 'Karka': 'कर्क', 'Simha': 'सिंह', 'Kanya': 'कन्या', 'Tula': 'तूळ', 'Vrischika': 'वृश्चिक', 'Dhanu': 'धनू', 'Makara': 'मकर', 'Kumbha': 'कुंभ', 'Meena': 'मीन'
        }
    },
    'bn': {
        'lunarmonth': {
            'Chaitra': 'চৈত্র', 'Vaishakha': 'বৈশাখ', 'Jyeshtha': 'জ্যৈষ্ঠ', 'Ashadha': 'আষাঢ়', 'Shravana': 'শ্রাবণ', 'Bhadrapada': 'ভাদ্রপদ', 'Ashwina': 'আশ্বিন', 'Kartika': 'কার্তিক', 'Margashirsha': 'মার্গশীর্ষ', 'Pausha': 'পৌষ', 'Magha': 'মাঘ', 'Phalguna': 'ফাল্গুন'
        },
        'nakshatra': {
            'Ashwini': 'অশ্বিনী', 'Bharani': 'ভরণী', 'Krittika': 'কৃত্তিকা', 'Rohini': 'রোহিণী', 'Mrigashira': 'মৃগশিরা', 'Ardra': 'আর্দ্রা', 'Punarvasu': 'পুনর্বসু', 'Pushya': 'পুষ্যা', 'Ashlesha': 'অশ্লেষা', 'Magha': 'মঘা', 'PurvaPhalguni': 'পূর্ব ফাল্গুনী', 'UttaraPhalguni': 'উত্তর ফাল্গুনী', 'Hasta': 'হস্তা', 'Chitra': 'চিত্রা', 'Swati': 'স্বাতী', 'Vishakha': 'বিশাখা', 'Anuradha': 'অনুরাধা', 'Jyeshtha': 'জ্যেষ্ঠা', 'Mula': 'মূলা', 'PurvaAshadha': 'পূর্বাষাঢ়া', 'UttaraAshadha': 'উত্তরাষাঢ়া', 'Shravana': 'শ্রবণা', 'Dhanishtha': 'ধনিষ্ঠা', 'Shatabhisha': 'শতভিষা', 'PurvaBhadrapada': 'পূর্ব ভাদ্রপদ', 'UttaraBhadrapada': 'উত্তর ভাদ্রপদ', 'Revati': 'রেবতী'
        },
        'paksha': {
            'Shukla': 'শুক্লপক্ষ', 'Krishna': 'কৃষ্ণপক্ষ'
        },
        'tithi': {
            'ShuklaPratipada': 'প্রতিপদ', 'ShuklaD2': 'দ্বিতীয়া', 'ShuklaD3': 'তৃতীয়া', 'ShuklaD4': 'চতুর্থী', 'ShuklaD5': 'পঞ্চমী', 'ShuklaD6': 'ষষ্ঠী', 'ShuklaD7': 'সप्तমী', 'ShuklaAshtami': 'অষ্টমী', 'ShuklaD9': 'নবমী', 'ShuklaD10': 'দশমী', 'ShuklaD11': 'একাদশী', 'ShuklaD12': 'দ্বাদশী', 'ShuklaD13': 'ত্রয়োদশী', 'ShuklaChaturdashi': 'চতুর্দশী', 'Purnima': 'পূর্ণিমা',
            'KrishnaPratipada': 'প্রতিপদ', 'KrishnaD2': 'দ্বিতীয়া', 'KrishnaD3': 'তৃতীয়া', 'KrishnaD4': 'চতুর্থী', 'KrishnaD5': 'পঞ্চমী', 'KrishnaD6': 'ষষ্ঠী', 'KrishnaD7': 'সপ্তমী', 'KrishnaAshtami': 'অষ্টমী', 'KrishnaD9': 'নবমী', 'KrishnaD10': 'দশমী', 'KrishnaD11': 'একাদশী', 'KrishnaD12': 'দ্বাদশী', 'KrishnaD13': 'ত্রয়োদশী', 'KrishnaChaturdashi': 'চতুর্দশী', 'Amavasya': 'অমাবস্যা'
        },
        'vara': {
            'Ravivara': 'রবিবার', 'Somavara': 'সোমবার', 'Mangalavara': 'মঙ্গলবার', 'Budhavara': 'বুধবার', 'Guruvara': 'বৃহস্পতিবার', 'Shukravara': 'শুক্রবার', 'Shanivara': 'শনিবার'
        },
        'yoga': {
            'Vishkumbha': 'বিষ্কন্ত', 'Preeti': 'প্রীতি', 'Ayushman': 'আয়ুষ্মান', 'Saubhagya': 'সৌভাগ্য', 'Shobhana': 'শোভন', 'Atiganda': 'অতিগণ্ড', 'Sukarman': 'সুকর্মা', 'Dhriti': 'ধৃতি', 'Shoola': 'শূল', 'Ganda': 'গণ্ড', 'Vriddhi': 'বৃদ্ধি', 'Dhruva': 'ধ্রুব', 'Vyaghata': 'ব্যাঘাত', 'Harshana': 'হর্ষণ', 'Vajra': 'বজ্র', 'Siddhi': 'সিদ্ধি', 'Vyatipata': 'ব্যতীপাত', 'Variyana': 'বরীয়ান', 'Parigha': 'পরিঘ', 'Shiva': 'শিব', 'Siddha': 'সিদ্ধ', 'Sadhya': 'সাধ্য', 'Shubha': 'শুভ', 'Shukla': 'শুক্ল', 'Brahma': 'ব্রহ্ম', 'Indra': 'ইন্দ্র', 'Vaidhriti': 'বৈধৃতি'
        },
        'karana': {
            'Bava': 'বব', 'Balava': 'বালব', 'Kaulava': 'কৌলব', 'Taitila': 'তৈতির', 'Garija': 'গর', 'Vanija': 'বণিজ', 'Vishti': 'বিষ্টি', 'Kimstughna': 'কিংস্স্তুঘ্ন', 'Shakuni': 'শকুনি', 'Chatushpada': 'চতুষ্পদ', 'Naga': 'নাগ'
        },
        'rashi': {
            'Mesha': 'মেষ', 'Vrishabha': 'বৃষ', 'Mithuna': 'মিথুন', 'Karka': 'কর্কট', 'Simha': 'সিংহ', 'Kanya': 'কন্যা', 'Tula': 'তুলা', 'Vrischika': 'বৃশ্চিক', 'Dhanu': 'ধনু', 'Makara': 'মকর', 'Kumbha': 'কুম্ভ', 'Meena': 'মীন'
        }
    },
    'ta': {
        'lunarmonth': {
            'Chaitra': 'சித்திரை', 'Vaishakha': 'வைகாசி', 'Jyeshtha': 'ஆனி', 'Ashadha': 'ஆடி', 'Shravana': 'ஆவணி', 'Bhadrapada': 'புரட்டாசி', 'Ashwina': 'ஐப்பசி', 'Kartika': 'கார்த்திகை', 'Margashirsha': 'மார்கழி', 'Pausha': 'தை', 'Magha': 'மாசி', 'Phalguna': 'பங்குனி'
        },
        'nakshatra': {
            'Ashwini': 'அஸ்வினி', 'Bharani': 'பரணி', 'Krittika': 'கார்த்திகை', 'Rohini': 'ரோகிணி', 'Mrigashira': 'மிருகசீரிடம்', 'Ardra': 'திருவாதிரை', 'Punarvasu': 'புனர்பூசம்', 'Pushya': 'பூசம்', 'Ashlesha': 'ஆயில்யம்', 'Magha': 'மகம்', 'PurvaPhalguni': 'பூரம்', 'UttaraPhalguni': 'உத்திரம்', 'Hasta': 'அஸ்தம்', 'Chitra': 'சித்திரை', 'Swati': 'சுவாதி', 'Vishakha': 'விசாகம்', 'Anuradha': 'அனுஷம்', 'Jyeshtha': 'கேட்டை', 'Mula': 'மூலம்', 'PurvaAshadha': 'பூராடம்', 'UttaraAshadha': 'உத்திராடம்', 'Shravana': 'திருவோணம்', 'Dhanishtha': 'அவிட்டம்', 'Shatabhisha': 'சதயம்', 'PurvaBhadrapada': 'பூரட்டாதி', 'UttaraBhadrapada': 'உத்திரட்டாதி', 'Revati': 'ரேவதி'
        },
        'paksha': {
            'Shukla': 'சுக்ல பட்சம்', 'Krishna': 'கிருஷ்ண பட்சம்'
        },
        'tithi': {
            'ShuklaPratipada': 'பிரதமை', 'ShuklaD2': 'துவிதியை', 'ShuklaD3': 'திருதியை', 'ShuklaD4': 'சதுர்த்தி', 'ShuklaD5': 'பஞ்சமி', 'ShuklaD6': 'சஷ்டி', 'ShuklaD7': 'சப்தமி', 'ShuklaAshtami': 'அஷ்டமி', 'ShuklaD9': 'நவமி', 'ShuklaD10': 'தசமி', 'ShuklaD11': 'ஏகாதசி', 'ShuklaD12': 'துவாதசி', 'ShuklaD13': 'திரயோதசி', 'ShuklaChaturdashi': 'சதுர்த்தசி', 'Purnima': 'பௌர்ணமி',
            'KrishnaPratipada': 'பிரதமை', 'KrishnaD2': 'துவிதியை', 'KrishnaD3': 'திருதியை', 'KrishnaD4': 'சதுர்த்தி', 'KrishnaD5': 'பஞ்சமி', 'KrishnaD6': 'சஷ்டி', 'KrishnaD7': 'சப்தமி', 'KrishnaAshtami': 'அஷ்டமி', 'KrishnaD9': 'நவமி', 'KrishnaD10': 'தசமி', 'KrishnaD11': 'ஏகாதசி', 'KrishnaD12': 'துவாதசி', 'KrishnaD13': 'திரயோதசி', 'KrishnaChaturdashi': 'சதுர்த்தசி', 'Amavasya': 'அமாவாசை'
        },
        'vara': {
            'Ravivara': 'ஞாயிறு', 'Somavara': 'திங்கள்', 'Mangalavara': 'செவ்வாய்', 'Budhavara': 'புதன்', 'Guruvara': 'வியாழன்', 'Shukravara': 'வெள்ளி', 'Shanivara': 'சனி'
        },
        'yoga': {
            'Vishkumbha': 'விஷ்கம்பம்', 'Preeti': 'பிரிதி', 'Ayushman': 'ஆயுஷ்மான்', 'Saubhagya': 'சௌபாக்கியம்', 'Shobhana': 'சோபனம்', 'Atiganda': 'அதிகண்டம்', 'Sukarman': 'சுகர்மம்', 'Dhriti': 'திருதி', 'Shoola': 'சூலம்', 'Ganda': 'கண்டம்', 'Vriddhi': 'விருத்தி', 'Dhruva': 'துருவம்', 'Vyaghata': 'வியாகாதம்', 'Harshana': 'ஹர்ஷணம்', 'Vajra': 'வஜ்ரம்', 'Siddhi': 'சித்தி', 'Vyatipata': 'வியதிபாதம்', 'Variyana': 'வரியான்', 'Parigha': 'பரிகம்', 'Shiva': 'சிவம்', 'Siddha': 'சித்தம்', 'Sadhya': 'சாத்தியம்', 'Shubha': 'சுபம்', 'Shukla': 'சுக்கிலம்', 'Brahma': 'பிரம்மம்', 'Indra': 'இந்திரம்', 'Vaidhriti': 'வைதிருதி'
        },
        'karana': {
            'Bava': 'பவம்', 'Balava': 'பாலவம்', 'Kaulava': 'கௌலவம்', 'Taitila': 'தைதுலம்', 'Garija': 'கரசை', 'Vanija': 'வனிசை', 'Vishti': 'விஷ்டி', 'Kimstughna': 'கிமஸ்துக்னம்', 'Shakuni': 'சகுனி', 'Chatushpada': 'சதுஷ்பாதம்', 'Naga': 'நாகம்'
        },
        'rashi': {
            'Mesha': 'மேஷம்', 'Vrishabha': 'ரிஷபம்', 'Mithuna': 'மிதுனம்', 'Karka': 'கடகம்', 'Simha': 'சிம்மம்', 'Kanya': 'கன்னி', 'Tula': 'துலாம்', 'Vrischika': 'விருச்சிகம்', 'Dhanu': 'தனுசு', 'Makara': 'மகரம்', 'Kumbha': 'கும்பம்', 'Meena': 'மீனம்'
        }
    },
    'te': {
        'lunarmonth': {
            'Chaitra': 'చైత్రము', 'Vaishakha': 'వైశాఖము', 'Jyeshtha': 'జ్యేష్ఠము', 'Ashadha': 'ఆషాఢము', 'Shravana': 'శ్రావణము', 'Bhadrapada': 'భాద్రపదము', 'Ashwina': 'ఆశ్వయుజము', 'Kartika': 'కార్తీకము', 'Margashirsha': 'మార్గశిరము', 'Pausha': 'పుష్యము', 'Magha': 'మాఘము', 'Phalguna': 'ఫాల్గుణము'
        },
        'nakshatra': {
            'Ashwini': 'అశ్విని', 'Bharani': 'భరణి', 'Krittika': 'కృత్తిక', 'Rohini': 'రోహిణి', 'Mrigashira': 'మృగశిర', 'Ardra': 'ఆరుద్ర', 'Punarvasu': 'పునర్వసు', 'Pushya': 'పుష్యమి', 'Ashlesha': 'ఆశ్లేష', 'Magha': 'మఖ', 'PurvaPhalguni': 'పూర్వ ఫల్గుణి', 'UttaraPhalguni': 'ఉత్తర ఫల్గుణి', 'Hasta': 'హస్త', 'Chitra': 'చిత్త', 'Swati': 'స్వాతి', 'Vishakha': 'విశాఖ', 'Anuradha': 'అనూరాధ', 'Jyeshtha': 'జ్యేష్ఠ', 'Mula': 'మూల', 'PurvaAshadha': 'పూర్వాషాఢ', 'UttaraAshadha': 'ఉత్తరాషాఢ', 'Shravana': 'శ్రవణం', 'Dhanishtha': 'ధనిష్ఠ', 'Shatabhisha': 'శతభిషం', 'PurvaBhadrapada': 'పూర్వాభాద్ర', 'UttaraBhadrapada': 'ఉత్తరాభాద్ర', 'Revati': 'రేవతి'
        },
        'paksha': {
            'Shukla': 'శుక్ల పక్షము', 'Krishna': 'కృష్ణ పక్షము'
        },
        'tithi': {
            'ShuklaPratipada': 'పాడ్యమి', 'ShuklaD2': 'విదియ', 'ShuklaD3': 'తదియ', 'ShuklaD4': 'చవితి', 'ShuklaD5': 'పంచమి', 'ShuklaD6': 'షష్ఠి', 'ShuklaD7': 'సప్తమి', 'ShuklaAshtami': 'అష్టమి', 'ShuklaD9': 'నవమి', 'ShuklaD10': 'దశమి', 'ShuklaD11': 'ఏకాదశి', 'ShuklaD12': 'ద్వాదశి', 'ShuklaD13': 'త్రయోదశి', 'ShuklaChaturdashi': 'చతుర్దశి', 'Purnima': 'పౌర్ణమి',
            'KrishnaPratipada': 'పాడ్యమి', 'KrishnaD2': 'విదియ', 'KrishnaD3': 'తదియ', 'KrishnaD4': 'చవితి', 'KrishnaD5': 'పంచమి', 'KrishnaD6': 'షష్ఠి', 'KrishnaD7': 'సప్తమి', 'KrishnaAshtami': 'అష్టమి', 'KrishnaD9': 'నవమి', 'KrishnaD10': 'దశమి', 'KrishnaD11': 'ఏకాదశి', 'KrishnaD12': 'ద్వాదశి', 'KrishnaD13': 'త్రయోదశి', 'KrishnaChaturdashi': 'చతుర్దశి', 'Amమావాస్య': 'అమావాస్య'
        },
        'vara': {
            'Ravivara': 'ఆదివారం', 'Somavara': 'సోమవారం', 'Mangalavara': 'మంగళవారం', 'Budhavara': 'బుధవారం', 'Guruvara': 'గురువారం', 'Shukravara': 'శుక్రవారం', 'Shanivara': 'శనివారం'
        },
        'yoga': {
            'Vishkumbha': 'విష్కంభ', 'Preeti': 'ప్రీతి', 'Ayushman': 'ఆయుష్మాన్', 'Saubhagya': 'సౌభాగ్య', 'Shobhana': 'శోభన', 'Atiganda': 'అతిగండ', 'Sukarman': 'సుకర్మ', 'Dhriti': 'ధృతి', 'Shoola': 'శూల', 'Ganda': 'గండ', 'Vriddhi': 'వృధ్ధి', 'Dhruva': 'ధృవ', 'Vyaghata': 'వ్యాఘాత', 'Harshana': 'హర్షణ', 'Vajra': 'వజ్ర', 'Siddhi': 'సిద్ధి', 'Vyatipata': 'వ్యతీపాత', 'Variyana': 'వరియాన్', 'Parigha': 'పరిఘ', 'Shiva': 'శివ', 'Siddha': 'సిద్ధ', 'Sadhya': 'సాధ్య', 'Shubha': 'శుభ', 'Shukla': 'శుక్ల', 'Brahma': 'బ్రహ్మ', 'Indra': 'ఇంద్ర', 'Vaidhriti': 'వైధృతి'
        },
        'karana': {
            'Bava': 'బవ', 'Balava': 'బాలవ', 'Kaulava': 'కౌలవ', 'Taitila': 'తైతుల', 'Garija': 'గరజి', 'Vanija': 'వణిజ', 'Vishti': 'విష్టి', 'Kimstughna': 'కింస్తుఘ్న', 'Shakuni': 'శకుని', 'Chatushpada': 'చతుష్పద', 'Naga': 'నాగ'
        },
        'rashi': {
            'Mesha': 'మేషం', 'Vrishabha': 'వృషభం', 'Mithuna': 'మిథునం', 'Karka': 'కర్కాటకం', 'Simha': 'సింహం', 'Kanya': 'కన్య', 'Tula': 'తుల', 'Vrischika': 'వృశ్చికం', 'Dhanu': 'ధనుస్సు', 'Makara': 'మకరం', 'Kumbha': 'కుంభం', 'Meena': 'మీనం'
        }
    },
    'kn': {
        'lunarmonth': {
            'Chaitra': 'ಚೈತ್ರ', 'Vaishakha': 'ವೈಶಾಖ', 'Jyeshtha': 'ಜ್ಯೇಷ್ಠ', 'Ashadha': 'ಆಷಾಢ', 'Shravana': 'ಶ್ರಾವಣ', 'Bhadrapada': 'ಭಾದ್ರಪದ', 'Ashwina': 'ಆಶ್ವಯುಜ', 'Kartika': 'ಕಾರ್ತೀಕ', 'Margashirsha': 'ಮಾರ್ಗಶಿರ', 'Pausha': 'ಪುಷ್ಯ', 'Magha': 'ಮಾಘ', 'Phalguna': 'ಫಾಲ್ಗುಣ'
        },
        'nakshatra': {
            'Ashwini': 'ಅಶ್ವಿನಿ', 'Bharani': 'ಭರಣಿ', 'Krittika': 'ಕೃತ್ತಿಕಾ', 'Rohini': 'ರೋಹಿಣಿ', 'Mrigashira': 'ಮೃಗಶಿರ', 'Ardra': 'ಆರ್ದ್ರಾ', 'Punarvasu': 'ಪುನರ್ವಸು', 'Pushya': 'ಪುಷ್ಯ', 'Ashlesha': 'ಆಶ್ಲೇಷಾ', 'Magha': 'ಮಘಾ', 'PurvaPhalguni': 'ಪೂರ್ವ ಫಲ್ಗುಣಿ', 'UttaraPhalguni': 'ಉತ್ತರ ಫಲ್ಗುಣಿ', 'Hasta': 'ಹಸ್ತ', 'Chitra': 'ಚಿತ್ತಾ', 'Swati': 'ಸ್ವಾತಿ', 'Vishakha': 'ವಿಶಾಖಾ', 'Anuradha': 'ಅನೂರಾಧಾ', 'Jyeshtha': 'ಜ್ಯೇಷ್ಠಾ', 'Mula': 'ಮೂಲಾ', 'PurvaAshadha': 'ಪೂರ್ವಾಷಾಢಾ', 'UttaraAshadha': 'ಉತ್ತರಾಷಾಢಾ', 'Shravana': 'ಶ್ರವಣ', 'Dhanishtha': 'ಧನಿಷ್ಠಾ', 'Shatabhisha': 'ಶತಭಿಷಾ', 'PurvaBhadrapada': 'ಪೂರ್ವಾಭಾದ್ರಪದಾ', 'UttaraBhadrapada': 'ಉತ್ತರಾಭಾದ್ರಪದಾ', 'Revati': 'ರೇವತಿ'
        },
        'paksha': {
            'Shukla': 'ಶುಕ್ಲ ಪಕ್ಷ', 'Krishna': 'ಕೃಷ್ಣ ಪಕ್ಷ'
        },
        'tithi': {
            'ShuklaPratipada': 'ಪಾಡ್ಯ', 'ShuklaD2': 'ಬಿದಿಗೆ', 'ShuklaD3': 'ತದಿಗೆ', 'ShuklaD4': 'ಚೌತಿ', 'ShuklaD5': 'ಪಂಚಮಿ', 'ShuklaD6': 'ಷಷ್ಠಿ', 'ShuklaD7': 'ಸಪ್ತಮಿ', 'ShuklaAshtami': 'ಅಷ್ಟಮಿ', 'ShuklaD9': 'ನವಮಿ', 'ShuklaD10': 'ದಶಮಿ', 'ShuklaD11': 'ಏಕಾದಶಿ', 'ShuklaD12': 'ದ್ವಾದಶಿ', 'ShuklaD13': 'ತ್ರಯೋದಶಿ', 'ShuklaChaturdashi': 'ಚತುರ್ದಶಿ', 'Purnima': 'ಹುಣ್ಣಿಮೆ',
            'KrishnaPratipada': 'ಪಾಡ್ಯ', 'KrishnaD2': 'ಬಿದಿಗೆ', 'KrishnaD3': 'ತದಿಗೆ', 'KrishnaD4': 'ಚೌತಿ', 'KrishnaD5': 'ಪಂಚಮಿ', 'KrishnaD6': 'ಷಷ್ಠಿ', 'KrishnaD7': 'ಸಪ್ತಮಿ', 'KrishnaAshtami': 'ಅಷ್ಟಮಿ', 'KrishnaD9': 'ನವಮಿ', 'KrishnaD10': 'ದಶಮಿ', 'KrishnaD11': 'ಏಕಾದಶಿ', 'KrishnaD12': 'ದ್ವಾದಶಿ', 'KrishnaD13': 'ತ್ರಯೋದಶಿ', 'KrishnaChaturdashi': 'ಚತುರ್ದಶಿ', 'Amavasya': 'ಅಮಾವಾಸ್ಯೆ'
        },
        'vara': {
            'Ravivara': 'ಭಾನುವಾರ', 'Somavara': 'ಸೋಮವಾರ', 'Mangalavara': 'ಮಂಗಳವಾರ', 'Budhavara': 'ಬುಧವಾರ', 'Guruvara': 'ಗುರುವಾರ', 'Shukravara': 'ಶುಕ್ರವಾರ', 'Shanivara': 'ಶನಿವಾರ'
        },
        'yoga': {
            'Vishkumbha': 'ವಿಷ್ಕಂಭ', 'Preeti': 'ಪ್ರೀತಿ', 'Ayushman': 'ಆಯುಷ್ಮಾನ್', 'Saubhagya': 'ಸೌಭಾಗ್ಯ', 'Shobhana': 'ಶೋಭನ', 'Atiganda': 'ಅತಿಗಂಡ', 'Sukarman': 'ಸುಕರ್ಮ', 'Dhriti': 'ಧೃತಿ', 'Shoola': 'ಶೂಲ', 'Ganda': 'ಗಂಡ', 'Vriddhi': 'ವೃದ್ಧಿ', 'Dhruva': 'ಧ್ರುವ', 'Vyaghata': 'ವ್ಯಾಘಾತ', 'Harshana': 'ಹರ್ಷಣ', 'Vajra': 'ವಜ್ರ', 'Siddhi': 'ಸಿದ್ಧಿ', 'Vyatipata': 'ವ್ಯತೀಪಾತ', 'Variyana': 'ವರಿಯಾನ್', 'Parigha': 'ಪರಿಘ', 'Shiva': 'ಶಿವ', 'Siddha': 'ಸಿದ್ಧ', 'Sadhya': 'ಸಾಧ್ಯ', 'Shubha': 'ಶುಭ', 'Shukla': 'ಶುಕ್ಲ', 'Brahma': 'ಬ್ರಹ್ಮ', 'Indra': 'ಇಂದ್ರ', 'Vaidhriti': 'ವೈಧೃತಿ'
        },
        'karana': {
            'Bava': 'ಬವ', 'Balava': 'ಬಾಲವ', 'Kaulava': 'ಕೌಲವ', 'Taitila': 'ತೈತಿಲ', 'Garija': 'ಗರಜ', 'Vanija': 'ವಣಿಜ', 'Vishti': 'ವಿಷ್ಟಿ', 'Kimstughna': 'ಕಿಂಸ್ತುಘ್ನ', 'Shakuni': 'ಶಕುನಿ', 'Chatushpada': 'ಚತುಷ್ಪಾದ', 'Naga': 'ನಾಗ'
        },
        'rashi': {
            'Mesha': 'ಮೇಷ', 'Vrishabha': 'ವೃಷಭ', 'Mithuna': 'ಮಿಥುನ', 'Karka': 'ಕರ್ಕಾಟಕ', 'Simha': 'ಸಿಂಹ', 'Kanya': 'ಕನ್ಯಾ', 'Tula': 'ತುಲಾ', 'Vrischika': 'ವೃಶ್ಚಿಕ', 'Dhanu': 'ಧನುಸ್ಸು', 'Makara': 'ಮಕರ', 'Kumbha': 'ಕುಂಭ', 'Meena': 'ಮೀನ'
        }
    },
    'ml': {
        'lunarmonth': {
            'Chaitra': 'ചൈത്രം', 'Vaishakha': 'വൈശാഖം', 'Jyeshtha': 'ജ്യേഷ്ഠം', 'Ashadha': 'ആഷാഢം', 'Shravana': 'ശ്രാവണം', 'Bhadrapada': 'ഭാദ്രപദം', 'Ashwina': 'ആശ്വിനം', 'Kartika': 'കാർത്തികം', 'Margashirsha': 'മാർഗ്ഗശീർഷം', 'Pausha': 'പൗഷം', 'Magha': 'മാഘം', 'Phalguna': 'ഫാൽഗുനം'
        },
        'nakshatra': {
            'Ashwini': 'അശ്വതി', 'Bharani': 'ഭരണി', 'Krittika': 'കാർത്തിക', 'Rohini': 'രോഹിണി', 'Mrigashira': 'മകയിരം', 'Ardra': 'തിരുവാതിര', 'Punarvasu': 'പുണർതം', 'Pushya': 'പൂയം', 'Ashlesha': 'ആയില്യം', 'Magha': 'മകം', 'PurvaPhalguni': 'പൂരം', 'UttaraPhalguni': 'ഉത്രം', 'Hasta': 'അത്തം', 'Chitra': 'ചിത്തിര', 'Swati': 'ചോതി', 'Vishakha': 'വിശാഖം', 'Anuradha': 'അനിഴം', 'Jyeshtha': 'തൃക്കേട്ട', 'Mula': 'മൂലം', 'PurvaAshadha': 'പൂരാടം', 'UttaraAshadha': 'ഉത്രാടം', 'Shravana': 'തിരുവോണം', 'Dhanishtha': 'അവിട്ടം', 'Shatabhisha': 'ചതയം', 'PurvaBhadrapada': 'പൂരുരുട്ടാതി', 'UttaraBhadrapada': 'ഉത്തൃട്ടാതി', 'Revati': 'രേവതി'
        },
        'paksha': {
            'Shukla': 'ശുക്ല പക്ഷം', 'Krishna': 'കൃഷ്ണ പക്ഷം'
        },
        'tithi': {
            'ShuklaPratipada': 'പ്രഥമ', 'ShuklaD2': 'ദ്വിതീയ', 'ShuklaD3': 'തൃതീയ', 'ShuklaD4': 'ചതുർത്ഥി', 'ShuklaD5': 'പഞ്ചമി', 'ShuklaD6': 'ഷഷ്ഠി', 'ShuklaD7': 'സപ്തമി', 'ShuklaAshtami': 'അഷ്ടമി', 'ShuklaD9': 'നവമി', 'ShuklaD10': 'ദശമി', 'ShuklaD11': 'ഏകാദശി', 'ShuklaD12': 'ദ്വാദശി', 'ShuklaD13': 'ത്രയോദശി', 'ShuklaChaturdashi': 'ചതുർദ്ദശി', 'Purnima': 'പൗർണ്ണമി',
            'KrishnaPratipada': 'പ്രഥമ', 'KrishnaD2': 'ദ്വിതീയ', 'KrishnaD3': 'തൃതീയ', 'KrishnaD4': 'ചതുർത്ഥി', 'KrishnaD5': 'പഞ്ചമി', 'KrishnaD6': 'ഷഷ്ഠി', 'KrishnaD7': 'സപ്തമി', 'KrishnaAshtami': 'അഷ്ടമി', 'KrishnaD9': 'നവമി', 'KrishnaD10': 'ദശമി', 'KrishnaD11': 'ഏകാദശി', 'KrishnaD12': 'ദ്വാദശി', 'KrishnaD13': 'ത്രയോദശി', 'KrishnaChaturdashi': 'ചതുർദ്ദശി', 'Amavasya': 'അമാവാസി'
        },
        'vara': {
            'Ravivara': 'ഞായർ', 'Somavara': 'തിങ്കൾ', 'Mangalavara': 'ചൊവ്വ', 'Budhavara': 'ബുധൻ', 'Guruvara': 'വ്യാഴം', 'Shukravara': 'വെള്ളി', 'Shanivara': 'ശനി'
        },
        'yoga': {
            'Vishkumbha': 'വിഷ്കംഭം', 'Preeti': 'പ്രീതി', 'Ayushman': 'ആയുഷ്മാൻ', 'Saubhagya': 'സൗഭാഗ്യം', 'Shobhana': 'ശോഭനം', 'Atiganda': 'അതിഗണ്ഡം', 'Sukarman': 'സുകർമ്മം', 'Dhriti': 'ധൃതി', 'Shoola': 'ശൂലം', 'Ganda': 'ഗണ്ഡം', 'Vriddhi': 'വൃദ്ധി', 'Dhruva': 'ധ്രുവം', 'Vyaghata': 'വ്യാഘാതം', 'Harshana': 'ഹർഷണം', 'Vajra': 'വജ്രം', 'Siddhi': 'സിദ്ധി', 'Vyatipata': 'വ്യതീപാതം', 'Variyana': 'വരിയാൻ', 'Parigha': 'പരിഘം', 'Shiva': 'ശിവം', 'Siddha': 'സിദ്ധം', 'Sadhya': 'സാധ്യം', 'Shubha': 'ശുഭം', 'Shukla': 'ശുക്ലം', 'Brahma': 'ബ്രഹ്മം', 'Indra': 'ഇന്ദ്രൻ', 'Vaidhriti': 'വൈധൃതി'
        },
        'karana': {
            'Bava': 'ബവം', 'Balava': 'ബാലവം', 'Kaulava': 'കൗലവം', 'Taitila': 'തൈതിലം', 'Garija': 'ഗരജം', 'Vanija': 'വണിജം', 'Vishti': 'വിഷ്ടി', 'Kimstughna': 'കിംസ്തുഘ്നം', 'Shakuni': 'ശകുനി', 'Chatushpada': 'ചതുഷ്പാദം', 'Naga': 'നാഗം'
        },
        'rashi': {
            'Mesha': 'മേടം', 'Vrishabha': 'ഇടവം', 'Mithuna': 'മിഥുനം', 'Karka': 'കർക്കിടകം', 'Simha': 'ചിങ്ങം', 'Kanya': 'കന്നി', 'Tula': 'തുലാം', 'Vrischika': 'വൃശ്ചികം', 'Dhanu': 'ധനു', 'Makara': 'മകരം', 'Kumbha': 'കുംഭം', 'Meena': 'മീനം'
        }
    }
}

for lang_code, domains in langs.items():
    lang_dir = f"src/Localization/{lang_code}"
    if not os.path.exists(lang_dir):
        os.makedirs(lang_dir)
    
    for domain_name, data in domains.items():
        file_path = f"{lang_dir}/{domain_name}.php"
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write("<?php\n\nreturn [\n")
            for key, val in data.items():
                f.write(f"    '{key}' => '{val}',\n")
            f.write("];\n")
